<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table): void {
            if (! Schema::hasColumn('tenants', 'applied_system_version')) {
                $table->string('applied_system_version', 120)->nullable()->after('deactivated_at');
            }

            if (! Schema::hasColumn('tenants', 'applied_system_version_at')) {
                $table->timestamp('applied_system_version_at')->nullable()->after('applied_system_version');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table): void {
            if (Schema::hasColumn('tenants', 'applied_system_version_at')) {
                $table->dropColumn('applied_system_version_at');
            }

            if (Schema::hasColumn('tenants', 'applied_system_version')) {
                $table->dropColumn('applied_system_version');
            }
        });
    }
};
