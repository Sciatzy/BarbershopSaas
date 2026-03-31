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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('plan_tier');
            $table->string('primary_domain')->nullable()->unique()->after('status');
            $table->string('database_name')->nullable()->unique()->after('primary_domain');
            $table->timestamp('database_provisioned_at')->nullable()->after('database_name');
            $table->timestamp('activated_at')->nullable()->after('database_provisioned_at');
            $table->timestamp('deactivated_at')->nullable()->after('activated_at');
            $table->unsignedBigInteger('owner_user_id')->nullable()->after('deactivated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'primary_domain',
                'database_name',
                'database_provisioned_at',
                'activated_at',
                'deactivated_at',
                'owner_user_id',
            ]);
        });
    }
};
