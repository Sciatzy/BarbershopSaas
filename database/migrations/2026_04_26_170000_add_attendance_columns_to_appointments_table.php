<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            if (! Schema::hasColumn('appointments', 'arrived_at')) {
                $table->timestamp('arrived_at')->nullable()->after('appointment_datetime');
            }

            if (! Schema::hasColumn('appointments', 'late_marked_at')) {
                $table->timestamp('late_marked_at')->nullable()->after('arrived_at');
            }

            if (! Schema::hasColumn('appointments', 'no_show_marked_at')) {
                $table->timestamp('no_show_marked_at')->nullable()->after('late_marked_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $dropColumns = [];

            foreach (['arrived_at', 'late_marked_at', 'no_show_marked_at'] as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
