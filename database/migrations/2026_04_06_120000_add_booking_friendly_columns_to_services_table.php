<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table): void {
            if (! Schema::hasColumn('services', 'description')) {
                $table->text('description')->nullable()->after('name');
            } else {
                // column already exists — skipped
            }

            if (! Schema::hasColumn('services', 'base_price')) {
                $table->decimal('base_price', 8, 2)->nullable()->after('description');
            } else {
                // column already exists — skipped
            }

            if (! Schema::hasColumn('services', 'duration_min')) {
                $table->unsignedInteger('duration_min')->nullable()->after('base_price');
            } else {
                // column already exists — skipped
            }

            if (! Schema::hasColumn('services', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('duration_min');
            } else {
                // column already exists — skipped
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table): void {
            $dropColumns = [];

            if (Schema::hasColumn('services', 'description')) {
                $dropColumns[] = 'description';
            }

            if (Schema::hasColumn('services', 'base_price')) {
                $dropColumns[] = 'base_price';
            }

            if (Schema::hasColumn('services', 'duration_min')) {
                $dropColumns[] = 'duration_min';
            }

            if (Schema::hasColumn('services', 'is_active')) {
                $dropColumns[] = 'is_active';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
