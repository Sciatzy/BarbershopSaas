<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_releases', function (Blueprint $table): void {
            $table->id();
            $table->string('version', 120)->unique();
            $table->string('display_version', 128)->nullable();
            $table->string('publication_status', 20)->default('draft');
            $table->string('source', 64)->nullable();
            $table->string('branch', 120)->nullable();
            $table->string('commit_hash', 64)->nullable();
            $table->string('short_commit', 16)->nullable();
            $table->text('release_notes')->nullable();
            $table->foreignId('synced_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_releases');
    }
};
