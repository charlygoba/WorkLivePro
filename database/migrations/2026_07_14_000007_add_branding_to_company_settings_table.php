<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->string('brand_name', 100)->nullable()->after('company_name');
            $table->string('brand_subtitle', 160)->nullable()->after('brand_name');
            $table->string('brand_icon_path', 255)->nullable()->after('brand_subtitle');
            $table->string('color_primary', 7)->default('#4f46e5')->after('brand_icon_path');
            $table->string('color_secondary', 7)->default('#312e81')->after('color_primary');
            $table->string('color_accent', 7)->default('#06b6d4')->after('color_secondary');
            $table->string('color_sidebar', 7)->default('#0f172a')->after('color_accent');
            $table->string('color_sidebar_text', 7)->default('#cbd5e1')->after('color_sidebar');
            $table->string('color_page', 7)->default('#f8fafc')->after('color_sidebar_text');
            $table->string('color_surface', 7)->default('#ffffff')->after('color_page');
            $table->string('color_text', 7)->default('#0f172a')->after('color_surface');
            $table->string('menu_dashboard', 80)->nullable()->after('color_text');
            $table->string('menu_employees', 80)->nullable()->after('menu_dashboard');
            $table->string('menu_reports', 80)->nullable()->after('menu_employees');
            $table->string('menu_policies', 80)->nullable()->after('menu_reports');
            $table->string('menu_time_clock', 80)->nullable()->after('menu_policies');
            $table->string('menu_settings', 80)->nullable()->after('menu_time_clock');
            $table->string('menu_system', 80)->nullable()->after('menu_settings');
            $table->string('menu_administrators', 80)->nullable()->after('menu_system');
            $table->string('menu_personalization', 80)->nullable()->after('menu_administrators');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['brand_name','brand_subtitle','brand_icon_path','color_primary','color_secondary','color_accent','color_sidebar','color_sidebar_text','color_page','color_surface','color_text','menu_dashboard','menu_employees','menu_reports','menu_policies','menu_time_clock','menu_settings','menu_system','menu_administrators','menu_personalization']);
        });
    }
};
