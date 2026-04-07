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
            if (! Schema::hasColumn('appointments', 'staff_id')) {
                $table->foreignId('staff_id')->nullable()->after('service_id')->constrained('users')->nullOnDelete();
            } else {
                // column already exists — skipped
            }

            if (! Schema::hasColumn('appointments', 'total_price')) {
                $table->decimal('total_price', 8, 2)->nullable()->after('status');
            } else {
                // column already exists — skipped
            }

            if (! Schema::hasColumn('appointments', 'notes')) {
                $table->text('notes')->nullable()->after('total_price');
            } else {
                // column already exists — skipped
            }

            if (! Schema::hasColumn('appointments', 'booked_at')) {
                $table->timestamp('booked_at')->nullable()->after('notes');
            } else {
                // column already exists — skipped
            }

            if (! Schema::hasColumn('appointments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('booked_at');
            } else {
                // column already exists — skipped
            }

            if (! Schema::hasColumn('appointments', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('completed_at');
            } else {
                // column already exists — skipped
            }
        });

        // Map old statuses to the new queue flow states before tightening enum values.
        DB::table('appointments')->where('status', 'pending')->update(['status' => 'queued']);
        DB::table('appointments')->where('status', 'confirmed')->update(['status' => 'in_progress']);

        Schema::table('appointments', function (Blueprint $table): void {
            if (Schema::hasColumn('appointments', 'status')) {
                $table->enum('status', ['queued', 'in_progress', 'completed', 'cancelled'])->default('queued')->change();
            } else {
                // column already exists — skipped
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            if (Schema::hasColumn('appointments', 'status')) {
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending')->change();
            }

            $foreignNames = [
                'appointments_staff_id_foreign',
            ];

            foreach ($foreignNames as $foreignName) {
                if ($this->foreignKeyExists('appointments', $foreignName)) {
                    $table->dropForeign($foreignName);
                }
            }

            $dropColumns = [];

            foreach (['staff_id', 'total_price', 'notes', 'booked_at', 'completed_at', 'created_by'] as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = "FOREIGN KEY" LIMIT 1',
            [$database, $table, $name],
        );

        return $result !== null;
    }
};
