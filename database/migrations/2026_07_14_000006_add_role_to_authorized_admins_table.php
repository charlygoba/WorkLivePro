<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('authorized_admins', 'role')) {
            Schema::table('authorized_admins', function (Blueprint $table) {
                $table->string('role', 30)->default('admin')->after('is_super_admin');
            });
        }

        DB::table('authorized_admins')->where('is_super_admin', true)->update(['role' => 'super_admin']);
        DB::table('authorized_admins')->whereNull('role')->orWhere('role', '')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('authorized_admins', 'role')) {
            Schema::table('authorized_admins', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
