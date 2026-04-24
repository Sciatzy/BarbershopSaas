<?php

use App\Services\SystemReleaseSyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('system:sync-latest-release {--no-fetch : Skip git fetch and use local metadata fallback}', function (SystemReleaseSyncService $syncService): int {
    $release = $syncService->syncLatest(
        actor: null,
        fetchRemote: ! $this->option('no-fetch'),
    );

    $this->info('Synced latest release: '.($release->display_version ?: $release->version));
    $this->line('Source: '.($release->source ?? 'n/a'));
    $this->line('Branch: '.($release->branch ?? 'n/a'));
    $this->line('Commit: '.($release->short_commit ?? $release->commit_hash ?? 'n/a'));

    return self::SUCCESS;
})->purpose('Fetch latest system release metadata from configured git remote and persist it.');
