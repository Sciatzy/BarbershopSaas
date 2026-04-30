<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('barber_cashouts')) {
            return;
        }

        Schema::create('barber_cashouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('barber_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->integer('points');
            $table->decimal('amount_php', 10, 2);

            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');

            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->string('notes', 255)->nullable();
            $table->string('rejection_reason', 255)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barber_cashouts');
    }
};
