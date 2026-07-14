<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('color_logo_background', 7)->default('#4f46e5')->after('color_text');
            $table->boolean('logo_background_enabled')->default(true)->after('color_logo_background');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['color_logo_background', 'logo_background_enabled']);
        });
    }
};
