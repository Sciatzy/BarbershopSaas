<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_ticket_messages')) {
            return;
        }

        Schema::table('support_ticket_messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('support_ticket_messages', 'attachment_path')) {
                $table->string('attachment_path', 512)->nullable()->after('message');
            }

            if (! Schema::hasColumn('support_ticket_messages', 'attachment_original_name')) {
                $table->string('attachment_original_name', 255)->nullable()->after('attachment_path');
            }

            if (! Schema::hasColumn('support_ticket_messages', 'attachment_mime')) {
                $table->string('attachment_mime', 100)->nullable()->after('attachment_original_name');
            }

            if (! Schema::hasColumn('support_ticket_messages', 'attachment_size')) {
                $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('support_ticket_messages')) {
            return;
        }

        Schema::table('support_ticket_messages', function (Blueprint $table): void {
            if (Schema::hasColumn('support_ticket_messages', 'attachment_size')) {
                $table->dropColumn('attachment_size');
            }

            if (Schema::hasColumn('support_ticket_messages', 'attachment_mime')) {
                $table->dropColumn('attachment_mime');
            }

            if (Schema::hasColumn('support_ticket_messages', 'attachment_original_name')) {
                $table->dropColumn('attachment_original_name');
            }

            if (Schema::hasColumn('support_ticket_messages', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
        });
    }
};
