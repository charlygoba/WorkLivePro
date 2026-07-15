<?php

namespace App\Console\Commands;

use App\Services\ActivityAggregationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConsolidateActivityEvents extends Command
{
    protected $signature = 'activity:consolidate
        {--company= : Empresa a procesar}
        {--from= : Fecha inicial local YYYY-MM-DD}
        {--to= : Fecha final local YYYY-MM-DD}
        {--limit=2500 : Eventos por bloque}
        {--cleanup : Eliminar detalle únicamente hasta el límite seguro de 30 días}';

    protected $description = 'Consolida eventos detallados en buckets y resúmenes por bloques';

    public function handle(ActivityAggregationService $service): int
    {
        $companyId = (string) ($this->option('company') ?: config('worklive.company_id'));
        $timezone = DB::table('company_settings')->where('company_id', $companyId)->value('timezone') ?: config('app.timezone', 'UTC');
        $from = (string) ($this->option('from') ?: now($timezone)->subDays(30)->toDateString());
        $to = (string) ($this->option('to') ?: now($timezone)->toDateString());

        try {
            $fromUtc = Carbon::createFromFormat('Y-m-d', $from, $timezone)->startOfDay()->utc();
            $toUtc = Carbon::createFromFormat('Y-m-d', $to, $timezone)->endOfDay()->utc();
        } catch (\Throwable) {
            $this->error('Las fechas deben usar el formato YYYY-MM-DD.');
            return self::INVALID;
        }

        if ($fromUtc->greaterThanOrEqualTo($toUtc)) {
            $this->error('El periodo indicado no es válido.');
            return self::INVALID;
        }

        $limit = max(100, min(5000, (int) $this->option('limit')));
        $run = $service->start($companyId, $fromUtc, $toUtc, (bool) $this->option('cleanup'), 'cron');
        $this->info(sprintf('Consolidando %s a %s (%s)...', $from, $to, $timezone));

        do {
            $run = $service->process($run, $limit);
            $this->line(sprintf(
                '%s | procesados: %d | bloques: %d | eliminados: %d',
                $run->status,
                $run->processed_rows,
                $run->bucket_rows,
                $run->deleted_rows
            ));
        } while ($run->status !== 'completed');

        $this->info('Consolidación completada.');
        return self::SUCCESS;
    }
}
