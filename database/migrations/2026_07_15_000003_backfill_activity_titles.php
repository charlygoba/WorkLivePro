<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('activity_5m_buckets') || ! DB::getSchemaBuilder()->hasTable('activity_events')) {
            return;
        }

        DB::table('activity_5m_buckets')->whereNull('activity_title')->chunkById(250, function ($buckets): void {
            foreach ($buckets as $bucket) {
                $title = DB::table('activity_events')
                    ->where('company_id', $bucket->company_id)
                    ->where('employee_id', $bucket->employee_id)
                    ->where('event_type', $bucket->event_type)
                    ->whereBetween('event_timestamp', [$bucket->bucket_start_utc, $bucket->bucket_end_utc])
                    ->whereRaw("COALESCE(NULLIF(TRIM(app), ''), 'Sin aplicación') = ?", [$bucket->app])
                    ->whereRaw("COALESCE(NULLIF(TRIM(domain), ''), 'Sin dominio') = ?", [$bucket->domain])
                    ->whereNotNull('title')
                    ->where('title', '<>', '')
                    ->orderBy('event_timestamp')
                    ->value('title');

                if ($title) {
                    DB::table('activity_5m_buckets')
                        ->where('bucket_key', $bucket->bucket_key)
                        ->update(['activity_title' => $title, 'updated_at' => now()]);
                }
            }
        });
    }

    public function down(): void
    {
        // El backfill no se revierte: el dato proviene del evento original y no altera el contrato del cliente.
    }
};
