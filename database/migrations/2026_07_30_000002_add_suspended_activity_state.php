<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'daily_summaries' => 'total_suspended_seconds',
            'daily_app_summaries' => 'suspended_seconds',
            'daily_domain_summaries' => 'suspended_seconds',
            'activity_5m_buckets' => 'suspended_seconds',
        ];
        foreach ($columns as $table => $column) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, $column)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unsignedInteger($column)->default(0));
            }
        }

        if (! Schema::hasTable('activity_events')) return;

        DB::table('activity_events')->where('event_type', 'idle')->where('title', 'like', '%[SUSPENDIDO]%')->update(['event_type' => 'suspended']);
        $events = DB::table('activity_events')->where('event_type', 'suspended')->orderBy('event_timestamp')->get(['company_id', 'employee_id', 'event_timestamp', 'app', 'domain', 'duration']);
        $timezones = DB::table('company_settings')->pluck('timezone', 'company_id');

        foreach ($events as $event) {
            $duration = max(0, (int) $event->duration);
            if ($duration === 0) continue;
            $timezone = $timezones->get($event->company_id) ?: 'UTC';
            $date = Carbon::parse($event->event_timestamp, 'UTC')->setTimezone($timezone)->toDateString();
            $app = trim((string) ($event->app ?? '')) ?: 'Sin aplicación';
            $domain = trim((string) ($event->domain ?? '')) ?: 'Sin dominio';

            $summary = DB::table('daily_summaries')->where('company_id', $event->company_id)->where('employee_id', $event->employee_id)->where('summary_date', $date)->first();
            if ($summary) DB::table('daily_summaries')->where('id', $summary->id)->update(['total_idle_seconds' => max(0, (int) $summary->total_idle_seconds - $duration), 'total_suspended_seconds' => (int) $summary->total_suspended_seconds + $duration]);

            foreach ([['daily_app_summaries', 'app', $app], ['daily_domain_summaries', 'domain', $domain]] as [$table, $field, $value]) {
                $row = DB::table($table)->where('company_id', $event->company_id)->where('employee_id', $event->employee_id)->where('summary_date', $date)->where($field, $value)->first();
                if ($row) DB::table($table)->where('id', $row->id)->update(['idle_seconds' => max(0, (int) $row->idle_seconds - $duration), 'suspended_seconds' => (int) $row->suspended_seconds + $duration]);
            }
        }

        DB::table('activity_5m_buckets')->where('event_type', 'idle')->where('activity_title', 'like', '%[SUSPENDIDO]%')->update(['event_type' => 'suspended', 'suspended_seconds' => DB::raw('suspended_seconds + idle_seconds'), 'idle_seconds' => 0]);
    }

    public function down(): void
    {
        foreach (['daily_summaries' => 'total_suspended_seconds', 'daily_app_summaries' => 'suspended_seconds', 'daily_domain_summaries' => 'suspended_seconds', 'activity_5m_buckets' => 'suspended_seconds'] as $table => $column) {
            if (Schema::hasColumn($table, $column)) Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
        }
    }
};
