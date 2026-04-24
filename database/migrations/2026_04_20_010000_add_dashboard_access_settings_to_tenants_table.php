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

        if (! Schema::hasColumn('tenants', 'dashboard_access_settings')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->json('dashboard_access_settings')->nullable()->after('hero_image_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenants')) {
            return;
        }

        if (Schema::hasColumn('tenants', 'dashboard_access_settings')) {
            Schema::table('tenants', function (Blueprint $table): void {
                $table->dropColumn('dashboard_access_settings');
            });
        }
    }
};
