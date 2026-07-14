<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('authorized_admins', 'is_super_admin')) {
            Schema::table('authorized_admins', function (Blueprint $table) {
                $table->boolean('is_super_admin')->default(false)->after('last_login_at');
            });
        }

        $companyId = config('worklive.company_id');
        $bootstrapEmail = strtolower((string) env('ADMIN_BOOTSTRAP_EMAIL', ''));
        $bootstrap = $bootstrapEmail !== ''
            ? DB::table('authorized_admins')->where('company_id', $companyId)->where('email', $bootstrapEmail)->first()
            : null;

        if ($bootstrap) {
            DB::table('authorized_admins')->where('company_id', $companyId)->where('email', $bootstrap->email)->update(['is_super_admin' => true]);
            return;
        }

        $currentSuperAdmin = DB::table('authorized_admins')->where('company_id', $companyId)->where('is_super_admin', true)->exists();
        if (! $currentSuperAdmin) {
            $firstAdmin = DB::table('authorized_admins')->where('company_id', $companyId)->orderBy('added_at')->orderBy('email')->first();
            if ($firstAdmin) {
                DB::table('authorized_admins')->where('company_id', $companyId)->where('email', $firstAdmin->email)->update(['is_super_admin' => true]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('authorized_admins', 'is_super_admin')) {
            Schema::table('authorized_admins', function (Blueprint $table) {
                $table->dropColumn('is_super_admin');
            });
        }
    }
};
