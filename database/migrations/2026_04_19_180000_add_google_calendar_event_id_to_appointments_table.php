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
            if (! Schema::hasColumn('appointments', 'google_calendar_event_id')) {
                $table->string('google_calendar_event_id')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table): void {
            if (Schema::hasColumn('appointments', 'google_calendar_event_id')) {
                $table->dropColumn('google_calendar_event_id');
            }
        });
    }
};
