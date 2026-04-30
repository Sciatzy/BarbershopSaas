<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedule_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('barber_id')->constrained('users')->cascadeOnDelete();
            $table->date('schedule_date');
            $table->boolean('is_working')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'barber_id', 'schedule_date'], 'schedule_overrides_unique_day');
            $table->index(['tenant_id', 'schedule_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_overrides');
    }
};
