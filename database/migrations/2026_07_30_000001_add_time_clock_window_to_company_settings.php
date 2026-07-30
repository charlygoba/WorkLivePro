<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('company_settings', 'time_clock_before_hours')) {
            Schema::table('company_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('time_clock_before_hours')->default(1);
            });
        }
        if (! Schema::hasColumn('company_settings', 'time_clock_after_hours')) {
            Schema::table('company_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('time_clock_after_hours')->default(2);
            });
        }
    }

    public function down(): void
    {
        foreach (['time_clock_before_hours', 'time_clock_after_hours'] as $column) {
            if (Schema::hasColumn('company_settings', $column)) {
                Schema::table('company_settings', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
