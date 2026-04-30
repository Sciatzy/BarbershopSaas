<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointments') || ! Schema::hasColumn('appointments', 'status')) {
            return;
        }

        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $column = $connection->selectOne(
            'SELECT IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$database, 'appointments', 'status'],
        );

        $isNullable = (($column->IS_NULLABLE ?? 'NO') === 'YES');
        $defaultValue = $column->COLUMN_DEFAULT ?? null;

        $allowed = [
            'pending',
            'confirmed',
            'queued',
            'arrived',
            'late',
            'in_progress',
            'completed',
            'no_show',
            'cancelled',
        ];

        $enumSql = "ENUM('".implode("','", $allowed)."')";
        $nullSql = $isNullable ? 'NULL' : 'NOT NULL';
        $defaultSql = '';

        if ($defaultValue !== null && in_array((string) $defaultValue, $allowed, true)) {
            $defaultSql = " DEFAULT '".str_replace("'", "''", (string) $defaultValue)."'";
        } elseif ($defaultValue === null && ! $isNullable) {
            // Keep existing behavior from earlier migrations (default queued) to avoid insert failures.
            $defaultSql = " DEFAULT 'queued'";
        }

        DB::statement("ALTER TABLE `appointments` MODIFY `status` {$enumSql} {$nullSql}{$defaultSql}");
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments') || ! Schema::hasColumn('appointments', 'status')) {
            return;
        }

        // Best-effort rollback: coerce newer statuses back to queued before narrowing the enum.
        DB::table('appointments')
            ->whereIn('status', ['pending', 'confirmed', 'arrived', 'late', 'no_show'])
            ->update(['status' => 'queued']);

        DB::statement("ALTER TABLE `appointments` MODIFY `status` ENUM('queued','in_progress','completed','cancelled') NOT NULL DEFAULT 'queued'");
    }
};
