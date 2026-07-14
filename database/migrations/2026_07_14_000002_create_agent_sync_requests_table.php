<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_sync_requests', function (Blueprint $table) {
            $table->string('id', 80)->primary();
            $table->string('company_id', 120)->index();
            $table->string('employee_id', 120)->index();
            $table->string('status', 20)->default('requested')->index();
            $table->string('requested_by', 255)->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->index(['company_id', 'employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_sync_requests');
    }
};
