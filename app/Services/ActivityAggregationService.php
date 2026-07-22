<?php

namespace App\Services;

use App\Models\AggregationRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ActivityAggregationService
{
    public function start(string $companyId, Carbon $fromUtc, Carbon $toUtc, bool $cleanup, ?string $requestedBy = null): AggregationRun
    {
        if ($fromUtc->greaterThanOrEqualTo($toUtc)) throw new RuntimeException('El periodo de consolidación no es válido.');

        $running = AggregationRun::where('company_id', $companyId)
            ->whereIn('status', ['pending', 'processing'])
            ->latest('created_at')->first();
        if ($running) return $running;

        $timezone = $this->corporateTimezone($companyId);
        $localFrom = $fromUtc->copy()->setTimezone($timezone)->toDateString();
        $localTo = $toUtc->copy()->setTimezone($timezone)->toDateString();

        return DB::transaction(function () use ($companyId, $fromUtc, $toUtc, $cleanup, $requestedBy, $localFrom, $localTo) {
            $run = AggregationRun::create([
                'company_id' => $companyId,
                'requested_by' => $requestedBy,
                'from_utc' => $fromUtc,
                'to_utc' => $toUtc,
                'cleanup_enabled' => $cleanup,
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // El periodo se reconstruye solo si todavía existe detalle crudo.
            // Si ya fue depurado, no se borran los históricos permanentes.
            $hasRaw = DB::table('activity_events')->where('company_id', $companyId)->whereBetween('event_timestamp', [$fromUtc, $toUtc])->exists();
            if ($hasRaw) {
                DB::table('activity_5m_buckets')->where('company_id', $companyId)->whereBetween('bucket_start_utc', [$fromUtc, $toUtc])->delete();
                DB::table('daily_app_summaries')->where('company_id', $companyId)->whereBetween('summary_date', [$localFrom, $localTo])->delete();
                DB::table('daily_domain_summaries')->where('company_id', $companyId)->whereBetween('summary_date', [$localFrom, $localTo])->delete();
                DB::table('daily_summaries')->where('company_id', $companyId)->whereBetween('summary_date', [$localFrom, $localTo])->delete();
            }

            return $run;
        });
    }

    public function process(AggregationRun|string $run, int $limit = 2500): AggregationRun
    {
        $run = $run instanceof AggregationRun ? $run : AggregationRun::findOrFail($run);
        if ($run->status === 'completed') return $run->fresh();
        if ($run->status === 'failed') throw new RuntimeException('La ejecución quedó marcada como fallida.');

        try {
            return DB::transaction(function () use ($run, $limit) {
                $run = AggregationRun::whereKey($run->id)->lockForUpdate()->firstOrFail();
                $run->update(['status' => 'processing', 'error_message' => null]);

                $query = DB::table('activity_events')
                    ->where('company_id', $run->company_id)
                    ->whereBetween('event_timestamp', [$run->from_utc, $run->to_utc])
                    ->orderBy('event_timestamp')->orderBy('id');
                if ($run->cursor_timestamp) {
                    $query->where(function ($q) use ($run) {
                        $q->where('event_timestamp', '>', $run->cursor_timestamp)
                            ->orWhere(function ($same) use ($run) {
                                $same->where('event_timestamp', $run->cursor_timestamp)->where('id', '>', $run->cursor_id);
                            });
                    });
                }
                $events = $query->limit(min(max($limit, 100), 5000))->get();
                if ($events->isEmpty()) return $this->finish($run);

                $timezone = $this->corporateTimezone($run->company_id);
                $buckets = [];
                $apps = [];
                $domains = [];
                $daily = [];
                $employeeMeta = DB::table('employees')->where('company_id', $run->company_id)->whereIn('id', $events->pluck('employee_id')->unique())->get()->keyBy('id');
                foreach ($events as $event) {
                    $timestamp = Carbon::parse($event->event_timestamp, 'UTC');
                    $duration = max(0, (int) $event->duration);
                    $active = $event->event_type === 'active' ? $duration : 0;
                    $idle = $event->event_type === 'idle' ? $duration : 0;
                    $app = trim((string) ($event->app ?? '')) ?: 'Sin aplicación';
                    $domain = trim((string) ($event->domain ?? '')) ?: 'Sin dominio';
                    $activityTitle = trim((string) ($event->title ?? '')) ?: null;
                    $bucketStart = $timestamp->copy()->startOfMinute()->minute((int) (floor($timestamp->minute / 5) * 5))->second(0);
                    $bucketEnd = $bucketStart->copy()->addMinutes(5);
                    $bucketKey = hash('sha256', implode('|', [$run->company_id, $event->employee_id, $bucketStart->toDateTimeString(), $event->event_type, $app, $domain]));
                    $this->addAggregate($buckets, $bucketKey, [
                        'bucket_key' => $bucketKey, 'company_id' => $run->company_id, 'employee_id' => $event->employee_id,
                        'bucket_start_utc' => $bucketStart, 'bucket_end_utc' => $bucketEnd, 'event_type' => $event->event_type,
                        'app' => $app, 'domain' => $domain, 'activity_title' => $activityTitle, 'active_seconds' => $active, 'idle_seconds' => $idle,
                        'event_count' => 1, 'first_event_at' => $timestamp, 'last_event_at' => $timestamp, 'agent_id' => $event->agent_id,
                    ]);
                    $summaryDate = $timestamp->copy()->setTimezone($timezone)->toDateString();
                    $dailyKey = hash('sha256', implode('|', [$run->company_id, $event->employee_id, $summaryDate]));
                    $meta = $employeeMeta->get($event->employee_id);
                    if (! isset($daily[$dailyKey])) {
                        $daily[$dailyKey] = ['id' => 'daily-'.substr($dailyKey, 0, 60), 'company_id' => $run->company_id, 'employee_id' => $event->employee_id, 'employee_name' => $event->employee_name ?: ($meta->name ?? $event->employee_id), 'department' => $event->department ?: ($meta->department ?? ''), 'country' => $meta->country ?? '', 'summary_date' => $summaryDate, 'total_active_seconds' => 0, 'total_idle_seconds' => 0, 'total_locked_seconds' => 0, 'first_activity' => $timestamp, 'last_activity' => $timestamp, 'top_apps' => json_encode([]), 'top_domains' => json_encode([])];
                    }
                    $daily[$dailyKey]['total_active_seconds'] += $active;
                    $daily[$dailyKey]['total_idle_seconds'] += $idle;
                    $daily[$dailyKey]['total_locked_seconds'] += $event->event_type === 'locked' ? $duration : 0;
                    $daily[$dailyKey]['first_activity'] = $timestamp < $daily[$dailyKey]['first_activity'] ? $timestamp : $daily[$dailyKey]['first_activity'];
                    $daily[$dailyKey]['last_activity'] = $timestamp > $daily[$dailyKey]['last_activity'] ? $timestamp : $daily[$dailyKey]['last_activity'];
                    $appKey = hash('sha256', implode('|', [$run->company_id, $event->employee_id, $summaryDate, $app]));
                    $this->addAggregate($apps, $appKey, ['summary_key' => $appKey, 'company_id' => $run->company_id, 'employee_id' => $event->employee_id, 'summary_date' => $summaryDate, 'app' => $app, 'active_seconds' => $active, 'idle_seconds' => $idle, 'event_count' => 1]);
                    $domainKey = hash('sha256', implode('|', [$run->company_id, $event->employee_id, $summaryDate, $domain]));
                    $this->addAggregate($domains, $domainKey, ['summary_key' => $domainKey, 'company_id' => $run->company_id, 'employee_id' => $event->employee_id, 'summary_date' => $summaryDate, 'domain' => $domain, 'active_seconds' => $active, 'idle_seconds' => $idle, 'event_count' => 1]);
                }

                $this->mergeBuckets($buckets);
                $this->mergeDaily($daily);
                $this->mergeSummary('daily_app_summaries', $apps, ['summary_key', 'company_id', 'employee_id', 'summary_date', 'app']);
                $this->mergeSummary('daily_domain_summaries', $domains, ['summary_key', 'company_id', 'employee_id', 'summary_date', 'domain']);

                $last = $events->last();
                $run->update([
                    'processed_rows' => $run->processed_rows + $events->count(),
                    'bucket_rows' => $run->bucket_rows + count($buckets),
                    'cursor_timestamp' => $last->event_timestamp,
                    'cursor_id' => $last->id,
                ]);
                return $run->fresh();
            });
        } catch (\Throwable $exception) {
            $run->update(['status' => 'failed', 'error_message' => Str::limit($exception->getMessage(), 4000)]);
            throw $exception;
        }
    }

    private function finish(AggregationRun $run): AggregationRun
    {
        $deleted = 0;
        if ($run->cleanup_enabled) {
            $cutoff = now('UTC')->subDays(30);
            $until = $run->to_utc->lessThan($cutoff) ? $run->to_utc : $cutoff;
            $deleted = DB::table('activity_events')->where('company_id', $run->company_id)->where('event_timestamp', '<=', $until)->delete();
        }
        $run->update(['status' => 'completed', 'deleted_rows' => $deleted, 'completed_at' => now()]);
        return $run->fresh();
    }

    private function addAggregate(array &$target, string $key, array $row): void
    {
        if (! isset($target[$key])) { $target[$key] = $row; return; }
        foreach (['active_seconds', 'idle_seconds', 'event_count'] as $field) $target[$key][$field] += $row[$field];
        if (array_key_exists('first_event_at', $row)) {
            $target[$key]['first_event_at'] = $row['first_event_at'] < $target[$key]['first_event_at'] ? $row['first_event_at'] : $target[$key]['first_event_at'];
            $target[$key]['last_event_at'] = $row['last_event_at'] > $target[$key]['last_event_at'] ? $row['last_event_at'] : $target[$key]['last_event_at'];
        }
        if (array_key_exists('activity_title', $row)) {
            $target[$key]['activity_title'] = $this->preferredActivityTitle($target[$key]['activity_title'] ?? null, $row['activity_title'] ?? null);
        }
    }

    private function mergeBuckets(array $rows): void
    {
        foreach ($rows as $row) {
            $existing = DB::table('activity_5m_buckets')->where('bucket_key', $row['bucket_key'])->first();
            if (! $existing) { DB::table('activity_5m_buckets')->insert($row + ['created_at' => now(), 'updated_at' => now()]); continue; }
            DB::table('activity_5m_buckets')->where('bucket_key', $row['bucket_key'])->update([
                'active_seconds' => $existing->active_seconds + $row['active_seconds'], 'idle_seconds' => $existing->idle_seconds + $row['idle_seconds'],
                'event_count' => $existing->event_count + $row['event_count'], 'first_event_at' => min($existing->first_event_at, (string) $row['first_event_at']),
                'last_event_at' => max($existing->last_event_at, (string) $row['last_event_at']),
                'activity_title' => $this->preferredActivityTitle($existing->activity_title, $row['activity_title'] ?? null), 'updated_at' => now(),
            ]);
        }
    }

    private function preferredActivityTitle(?string $current, ?string $candidate): ?string
    {
        $current = trim((string) $current);
        $candidate = trim((string) $candidate);

        if ($current === '') return $candidate !== '' ? $candidate : null;
        if ($candidate === '') return $current;

        // Cuando un bloque reúne varios eventos, conservar el título con más
        // contexto permite distinguir llamadas, reuniones y ventanas concretas
        // sin exigir cambios al contrato de ninguna versión del cliente.
        return mb_strlen($candidate) > mb_strlen($current) ? $candidate : $current;
    }

    private function mergeSummary(string $table, array $rows, array $keys): void
    {
        foreach ($rows as $row) {
            $existing = DB::table($table)->where('summary_key', $row['summary_key'])->first();
            if (! $existing) { DB::table($table)->insert($row + ['created_at' => now(), 'updated_at' => now()]); continue; }
            DB::table($table)->where('summary_key', $row['summary_key'])->update(['active_seconds' => $existing->active_seconds + $row['active_seconds'], 'idle_seconds' => $existing->idle_seconds + $row['idle_seconds'], 'event_count' => $existing->event_count + $row['event_count'], 'updated_at' => now()]);
        }
    }

    private function mergeDaily(array $rows): void
    {
        foreach ($rows as $row) {
            $existing = DB::table('daily_summaries')->where('company_id', $row['company_id'])->where('employee_id', $row['employee_id'])->where('summary_date', $row['summary_date'])->first();
            if (! $existing) { DB::table('daily_summaries')->insert($row); continue; }
            DB::table('daily_summaries')->where('id', $existing->id)->update([
                'total_active_seconds' => $existing->total_active_seconds + $row['total_active_seconds'],
                'total_idle_seconds' => $existing->total_idle_seconds + $row['total_idle_seconds'],
                'total_locked_seconds' => $existing->total_locked_seconds + $row['total_locked_seconds'],
                'first_activity' => min($existing->first_activity, (string) $row['first_activity']),
                'last_activity' => max($existing->last_activity, (string) $row['last_activity']),
            ]);
        }
    }

    private function corporateTimezone(string $companyId): string
    {
        return DB::table('company_settings')->where('company_id', $companyId)->value('timezone') ?: config('app.timezone', 'UTC');
    }
}
