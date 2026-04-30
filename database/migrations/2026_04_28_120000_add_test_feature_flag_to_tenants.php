<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'test_feature_enabled')) {
                $table->boolean('test_feature_enabled')->default(false)->after('status')->comment('v1.1.0: Test feature flag to verify release migration');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'test_feature_enabled')) {
                $table->dropColumn('test_feature_enabled');
            }
        });
    }
};
