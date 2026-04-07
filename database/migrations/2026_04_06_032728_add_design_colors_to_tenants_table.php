<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('brand_color')->nullable()->after('name')->comment('Hex color like #C9A84C');
            $table->string('brand_color_secondary')->nullable()->after('brand_color')->comment('Hex color like #B54B2A');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['brand_color', 'brand_color_secondary']);
        });
    }
};
