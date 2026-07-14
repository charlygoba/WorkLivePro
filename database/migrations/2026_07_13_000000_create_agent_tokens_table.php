<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('agent_tokens')) return;
        Schema::create('agent_tokens', function (Blueprint $table) {
            $table->string('id', 120)->primary();
            $table->string('company_id', 120)->index();
            $table->string('employee_id', 120)->index();
            $table->string('token_hash', 128)->unique();
            $table->string('device_id', 120)->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            // Las tablas heredadas usan una collation distinta; la integridad se valida
            // en el middleware/controlador para no alterar el esquema existente.
        });
    }
    public function down(): void { Schema::dropIfExists('agent_tokens'); }
};
