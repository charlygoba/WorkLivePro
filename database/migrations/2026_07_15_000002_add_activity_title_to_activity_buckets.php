<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_5m_buckets') && ! Schema::hasColumn('activity_5m_buckets', 'activity_title')) {
            Schema::table('activity_5m_buckets', function (Blueprint $table) {
                $table->string('activity_title', 1000)->nullable()->after('domain');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('activity_5m_buckets') && Schema::hasColumn('activity_5m_buckets', 'activity_title')) {
            Schema::table('activity_5m_buckets', function (Blueprint $table) {
                $table->dropColumn('activity_title');
            });
        }
    }
};
