<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('customer_theme')->default('dark')->after('brand_color_secondary');
            $table->string('customer_font')->default('dm_sans')->after('customer_theme');
            $table->string('customer_button_style')->default('rounded')->after('customer_font');
            $table->string('logo_path')->nullable()->after('customer_button_style');
            $table->string('hero_image_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'customer_theme',
                'customer_font',
                'customer_button_style',
                'logo_path',
                'hero_image_path',
            ]);
        });
    }
};
