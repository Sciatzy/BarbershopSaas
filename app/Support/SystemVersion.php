<?php

namespace App\Support;

class SystemVersion
{
    private ?array $resolved = null;

    /**
     * @return array<string, mixed>
     */
    public function resolve(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $git = $this->resolveGitMetadata();
        $version = $this->resolveVersionValue($git);
        $status = $this->resolveStatus($git);
        $displayVersion = str_starts_with(strtolower($version), 'v') ? $version : 'v'.$version;

        $this->resolved = [
            'app_name' => (string) config('app.name', 'Barbershop SaaS'),
            'environment' => (string) app()->environment(),
            'version' => $version,
            'display_version' => $displayVersion,
            'status' => $status,
            'branch' => $git['branch'],
            'commit' => $git['commit'],
            'short_commit' => $git['short_commit'],
            'tag' => $git['tag'],
            'source' => $git['source'],
            'generated_at' => now()->toIso8601String(),
        ];

        return $this->resolved;
    }

    /**
     * @param array<string, mixed> $git
     */
    private function resolveVersionValue(array $git): string
    {
        $configuredVersion = trim((string) config('app.version', ''));

        if ($configuredVersion !== '') {
            return $configuredVersion;
        }

        $tag = trim((string) ($git['tag'] ?? ''));

        if ($tag !== '') {
            return $tag;
        }

        $shortCommit = trim((string) ($git['short_commit'] ?? ''));

        return $shortCommit !== '' ? 'dev-'.$shortCommit : 'dev';
    }

    /**
     * @param array<string, mixed> $git
     */
    private function resolveStatus(array $git): string
    {
        $configuredStatus = trim((string) config('app.version_status', ''));

        if ($configuredStatus !== '') {
            return $configuredStatus;
        }

        if (($git['dirty'] ?? null) === true) {
            return 'dirty';
        }

        return app()->environment('production') ? 'release' : 'development';
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveGitMetadata(): array
    {
        $metadata = [
            'branch' => null,
            'commit' => null,
            'short_commit' => null,
            'tag' => null,
            'dirty' => null,
            'source' => 'unavailable',
        ];

        $headData = $this->readHeadFromGitDirectory();

        if ($headData !== null) {
            $metadata['branch'] = $headData['branch'] ?? null;
            $metadata['commit'] = $headData['commit'] ?? null;
            $metadata['short_commit'] = isset($headData['commit']) ? substr((string) $headData['commit'], 0, 7) : null;
            $metadata['source'] = 'git-files';
        }

        $branchFromCli = $this->runGitCommand(['rev-parse', '--abbrev-ref', 'HEAD']);

        if ($branchFromCli !== null && $branchFromCli !== 'HEAD') {
            $metadata['branch'] = $branchFromCli;
            $metadata['source'] = $metadata['source'] === 'unavailable' ? 'git-cli' : 'git-files+cli';
        }

        $commitFromCli = $this->runGitCommand(['rev-parse', 'HEAD']);

        if ($commitFromCli !== null) {
            $metadata['commit'] = $commitFromCli;
            $metadata['short_commit'] = substr($commitFromCli, 0, 7);
            $metadata['source'] = $metadata['source'] === 'unavailable' ? 'git-cli' : 'git-files+cli';
        }

        $tag = $this->runGitCommand(['describe', '--tags', '--abbrev=0']);

        if ($tag !== null) {
            $metadata['tag'] = $tag;
        }

        $dirtyOutput = $this->runGitCommand(['status', '--porcelain', '--untracked-files=no']);

        if ($dirtyOutput !== null) {
            $metadata['dirty'] = $dirtyOutput !== '';
        }

        return $metadata;
    }

    /**
     * @return array<string, string>|null
     */
    private function readHeadFromGitDirectory(): ?array
    {
        $gitDirectory = $this->resolveGitDirectory();

        if ($gitDirectory === null) {
            return null;
        }

        $headPath = $gitDirectory.DIRECTORY_SEPARATOR.'HEAD';

        if (! is_file($headPath)) {
            return null;
        }

        $headValue = trim((string) @file_get_contents($headPath));

        if ($headValue === '') {
            return null;
        }

        if (str_starts_with($headValue, 'ref: ')) {
            $ref = trim(substr($headValue, 5));
            $branch = basename($ref);
            $commit = $this->readCommitFromRef($gitDirectory, $ref);

            return [
                'branch' => $branch,
                'commit' => (string) ($commit ?? ''),
            ];
        }

        if (preg_match('/^[0-9a-f]{40}$/i', $headValue) === 1) {
            return [
                'branch' => 'detached',
                'commit' => strtolower($headValue),
            ];
        }

        return null;
    }

    private function readCommitFromRef(string $gitDirectory, string $ref): ?string
    {
        $refPath = $gitDirectory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $ref);

        if (is_file($refPath)) {
            $value = trim((string) @file_get_contents($refPath));

            return $value !== '' ? strtolower($value) : null;
        }

        $packedRefsPath = $gitDirectory.DIRECTORY_SEPARATOR.'packed-refs';

        if (! is_file($packedRefsPath)) {
            return null;
        }

        foreach (file($packedRefsPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if ($line[0] === '#') {
                continue;
            }

            $parts = preg_split('/\s+/', trim($line));

            if (! is_array($parts) || count($parts) < 2) {
                continue;
            }

            if (($parts[1] ?? '') === $ref) {
                return strtolower((string) $parts[0]);
            }
        }

        return null;
    }

    private function resolveGitDirectory(): ?string
    {
        $defaultGitDirectory = base_path('.git');

        if (is_dir($defaultGitDirectory)) {
            return $defaultGitDirectory;
        }

        if (! is_file($defaultGitDirectory)) {
            return null;
        }

        $gitFileContents = trim((string) @file_get_contents($defaultGitDirectory));

        if (! str_starts_with($gitFileContents, 'gitdir:')) {
            return null;
        }

        $relativeDirectory = trim(substr($gitFileContents, strlen('gitdir:')));

        if ($relativeDirectory === '') {
            return null;
        }

        $resolvedDirectory = realpath(base_path($relativeDirectory));

        return $resolvedDirectory !== false ? $resolvedDirectory : null;
    }

    /**
     * @param array<int, string> $arguments
     */
    private function runGitCommand(array $arguments): ?string
    {
        if (! $this->canRunShellCommands()) {
            return null;
        }

        $base = base_path();
        $command = 'git -C '.escapeshellarg($base).' '.implode(' ', array_map('escapeshellarg', $arguments)).' 2>/dev/null';

        $rawOutput = shell_exec($command);

        if (! is_string($rawOutput)) {
            return null;
        }

        return trim($rawOutput);
    }

    private function canRunShellCommands(): bool
    {
        if (! function_exists('shell_exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return ! in_array('shell_exec', $disabled, true);
    }
}
