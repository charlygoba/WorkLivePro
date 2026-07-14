<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('authorized_admins', 'last_login_at')) {
            Schema::table('authorized_admins', function (Blueprint $table) {
                $table->timestamp('last_login_at')->nullable()->after('password_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('authorized_admins', 'last_login_at')) {
            Schema::table('authorized_admins', function (Blueprint $table) {
                $table->dropColumn('last_login_at');
            });
        }
    }
};
