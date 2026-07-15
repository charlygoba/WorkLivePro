<?php

namespace App\Console\Commands;

use App\Services\ActivityAggregationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProtectActivityEvents extends Command
{
    protected $signature = 'activity:protect {--company= : Empresa a procesar} {--limit=2500 : Eventos por bloque}';
    protected $description = 'Consolida y elimina de forma segura eventos detallados con más de 30 días';

    public function handle(ActivityAggregationService $service): int
    {
        $companyId = (string) ($this->option('company') ?: config('worklive.company_id'));
        $cutoff = now('UTC')->subDays(30);
        $oldest = DB::table('activity_events')->where('company_id', $companyId)->where('event_timestamp', '<=', $cutoff)->min('event_timestamp');
        if (! $oldest) { $this->info('No hay eventos detallados con más de 30 días.'); return self::SUCCESS; }

        $run = $service->start($companyId, Carbon::parse($oldest, 'UTC'), $cutoff, true, 'cron');
        do {
            $run = $service->process($run, (int) $this->option('limit'));
            $this->line(sprintf('%s: %d procesados, %d grupos, %d eliminados', $run->status, $run->processed_rows, $run->bucket_rows, $run->deleted_rows));
        } while ($run->status !== 'completed');

        return self::SUCCESS;
    }
}
