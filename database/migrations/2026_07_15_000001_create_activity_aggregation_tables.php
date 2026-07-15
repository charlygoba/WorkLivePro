<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('activity_5m_buckets')) {
            Schema::create('activity_5m_buckets', function (Blueprint $table) {
                $table->id();
                $table->string('bucket_key', 64)->unique();
                $table->string('company_id', 120);
                $table->string('employee_id', 120);
                $table->dateTime('bucket_start_utc');
                $table->dateTime('bucket_end_utc');
                $table->string('event_type', 30);
                $table->string('app', 255)->default('');
                $table->string('domain', 255)->default('');
                $table->unsignedInteger('active_seconds')->default(0);
                $table->unsignedInteger('idle_seconds')->default(0);
                $table->unsignedInteger('event_count')->default(0);
                $table->dateTime('first_event_at')->nullable();
                $table->dateTime('last_event_at')->nullable();
                $table->string('agent_id', 120)->nullable();
                $table->timestamps();

                $table->index(['company_id', 'bucket_start_utc'], 'buckets_company_time_idx');
                $table->index(['company_id', 'employee_id', 'bucket_start_utc'], 'buckets_employee_time_idx');
            });
        }

        if (! Schema::hasTable('daily_app_summaries')) {
            Schema::create('daily_app_summaries', function (Blueprint $table) {
                $table->id();
                $table->string('summary_key', 64)->unique();
                $table->string('company_id', 120);
                $table->string('employee_id', 120);
                $table->date('summary_date');
                $table->string('app', 255)->default('Sin aplicación');
                $table->unsignedInteger('active_seconds')->default(0);
                $table->unsignedInteger('idle_seconds')->default(0);
                $table->unsignedInteger('event_count')->default(0);
                $table->timestamps();

                $table->index(['company_id', 'summary_date'], 'app_summary_company_date_idx');
                $table->index(['company_id', 'employee_id', 'summary_date'], 'app_summary_employee_date_idx');
            });
        }

        if (! Schema::hasTable('daily_domain_summaries')) {
            Schema::create('daily_domain_summaries', function (Blueprint $table) {
                $table->id();
                $table->string('summary_key', 64)->unique();
                $table->string('company_id', 120);
                $table->string('employee_id', 120);
                $table->date('summary_date');
                $table->string('domain', 255)->default('Sin dominio');
                $table->unsignedInteger('active_seconds')->default(0);
                $table->unsignedInteger('idle_seconds')->default(0);
                $table->unsignedInteger('event_count')->default(0);
                $table->timestamps();

                $table->index(['company_id', 'summary_date'], 'domain_summary_company_date_idx');
                $table->index(['company_id', 'employee_id', 'summary_date'], 'domain_summary_employee_date_idx');
            });
        }

        if (! Schema::hasTable('aggregation_runs')) {
            Schema::create('aggregation_runs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('company_id', 120);
                $table->string('requested_by', 255)->nullable();
                $table->dateTime('from_utc');
                $table->dateTime('to_utc');
                $table->boolean('cleanup_enabled')->default(false);
                $table->string('status', 30)->default('pending');
                $table->unsignedBigInteger('processed_rows')->default(0);
                $table->unsignedBigInteger('bucket_rows')->default(0);
                $table->unsignedBigInteger('deleted_rows')->default(0);
                $table->dateTime('cursor_timestamp')->nullable();
                $table->string('cursor_id', 120)->nullable();
                $table->text('error_message')->nullable();
                $table->dateTime('started_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status'], 'aggregation_company_status_idx');
            });
        }

        // activity_events ya tiene el índice por empresa y fecha. Solo se
        // agregan las combinaciones que faltan para filtros y purgas reales.
        if (Schema::hasTable('activity_events')) {
            $indexes = collect(\DB::select("SHOW INDEX FROM activity_events"))
                ->pluck('Key_name')->filter()->unique()->all();

            Schema::table('activity_events', function (Blueprint $table) use ($indexes) {
                if (! in_array('idx_company_employee_event_time', $indexes, true)) {
                    $table->index(['company_id', 'employee_id', 'event_timestamp'], 'idx_company_employee_event_time');
                }
                if (! in_array('idx_company_type_event_time', $indexes, true)) {
                    $table->index(['company_id', 'event_type', 'event_timestamp'], 'idx_company_type_event_time');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aggregation_runs');
        Schema::dropIfExists('daily_domain_summaries');
        Schema::dropIfExists('daily_app_summaries');
        Schema::dropIfExists('activity_5m_buckets');
        if (Schema::hasTable('activity_events')) {
            Schema::table('activity_events', function (Blueprint $table) {
                $table->dropIndex('idx_company_employee_event_time');
                $table->dropIndex('idx_company_type_event_time');
            });
        }
    }
};
