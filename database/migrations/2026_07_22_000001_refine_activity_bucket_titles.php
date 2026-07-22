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

        DB::table('activity_5m_buckets')->orderBy('id')->chunkById(250, function ($buckets): void {
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
                    ->orderByRaw('CHAR_LENGTH(title) DESC')
                    ->orderBy('event_timestamp')
                    ->value('title');

                if ($title && mb_strlen(trim((string) $title)) > mb_strlen(trim((string) ($bucket->activity_title ?? '')))) {
                    DB::table('activity_5m_buckets')
                        ->where('id', $bucket->id)
                        ->update(['activity_title' => $title, 'updated_at' => now()]);
                }
            }
        });
    }

    public function down(): void
    {
        // El enriquecimiento se deriva de eventos existentes y no requiere reversión.
    }
};
