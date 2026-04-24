<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_system_release_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('system_release_id')->constrained('system_releases')->cascadeOnDelete();
            $table->string('state', 20)->default('pending');
            $table->string('hold_note', 500)->nullable();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('applied_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'system_release_id'], 'tenant_release_unique');
            $table->index(['tenant_id', 'state'], 'tenant_release_state_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_system_release_states');
    }
};
