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
            if (! Schema::hasColumn('appointments', 'requires_customer_decision')) {
                $table->boolean('requires_customer_decision')->default(false)->after('status');
            }

            if (! Schema::hasColumn('appointments', 'proposed_replacement_barber_id')) {
                $table->foreignId('proposed_replacement_barber_id')->nullable()->after('barber_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('appointments', 'customer_decision_due_at')) {
                $table->timestamp('customer_decision_due_at')->nullable()->after('requires_customer_decision');
            }

            if (! Schema::hasColumn('appointments', 'emergency_reason')) {
                $table->string('emergency_reason', 255)->nullable()->after('customer_decision_due_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            $foreignNames = [
                'appointments_proposed_replacement_barber_id_foreign',
            ];

            foreach ($foreignNames as $foreignName) {
                try {
                    $table->dropForeign($foreignName);
                } catch (\Throwable) {
                    // Ignore missing constraint names across engines.
                }
            }

            $dropColumns = [];

            foreach (['requires_customer_decision', 'proposed_replacement_barber_id', 'customer_decision_due_at', 'emergency_reason'] as $column) {
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
