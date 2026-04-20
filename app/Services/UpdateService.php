<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class UpdateService
{
    private const LOCK_FILENAME = 'update-runner.lock';

    public function __construct(
        private readonly array $config,
        private readonly CommandRunner $commands,
        private readonly LoggerService $logger,
        private readonly VersionService $versions
    ) {
    }

    public function status(bool $checkRemote = false): array
    {
        $enabled = (bool) ($this->config['features']['update_runner'] ?? false);
        $repoPath = (string) ($this->config['updates']['repo_path'] ?? $this->config['paths']['root']);
        $branch = (string) ($this->config['updates']['branch'] ?? 'main');
        $gitBinary = $this->commands->findBinary(['git'], $this->config['commands']['git'] ?? null);
        $updateInProgress = $this->isUpdateInProgress();

        $status = [
            'enabled' => $enabled,
            'repo_path' => $repoPath,
            'branch' => $branch,
            'git_binary' => $gitBinary,
            'command_runner_available' => $this->commands->canRunCommands(),
            'is_repo' => false,
            'worktree_clean' => null,
            'current_branch' => null,
            'current_version' => $this->versions->current(),
            'local_commit' => null,
            'remote_commit' => null,
            'remote_version' => null,
            'commits_ahead' => null,
            'commits_behind' => null,
            'remote_checked' => false,
            'update_available' => false,
            'update_in_progress' => $updateInProgress,
            'can_open_runner' => false,
            'can_run' => false,
            'message' => '',
        ];

        if (!$enabled) {
            $status['message'] = 'Update-Funktion ist in der Konfiguration deaktiviert.';

            return $status;
        }

        if ($gitBinary === null) {
            $status['message'] = 'Git wurde auf dem Server nicht gefunden.';

            return $status;
        }

        if (!is_dir($repoPath)) {
            $status['message'] = 'Das konfigurierte Repository-Verzeichnis existiert nicht.';

            return $status;
        }

        $probe = $this->commands->run([$gitBinary, '-C', $repoPath, 'rev-parse', '--is-inside-work-tree']);

        if (!$probe['success'] || trim($probe['stdout']) !== 'true') {
            $status['message'] = 'Das konfigurierte Verzeichnis ist kein Git-Repository.';

            return $status;
        }

        $status['is_repo'] = true;
        $status['can_open_runner'] = true;

        $branchProbe = $this->commands->run([$gitBinary, '-C', $repoPath, 'rev-parse', '--abbrev-ref', 'HEAD']);
        if ($branchProbe['success']) {
            $status['current_branch'] = trim($branchProbe['stdout']);
        }

        $localCommitProbe = $this->commands->run([$gitBinary, '-C', $repoPath, 'rev-parse', 'HEAD']);
        if ($localCommitProbe['success']) {
            $status['local_commit'] = trim($localCommitProbe['stdout']);
        }

        $cleanProbe = $this->commands->run([$gitBinary, '-C', $repoPath, 'status', '--short']);
        if ($cleanProbe['success']) {
            $status['worktree_clean'] = trim($cleanProbe['stdout']) === '';
        }

        if ($updateInProgress) {
            $status['message'] = 'Ein Update-Lauf ist bereits aktiv.';

            return $status;
        }

        if (!$checkRemote) {
            $status['can_run'] = $status['is_repo'] && $status['worktree_clean'] === true;
            $status['message'] = $status['can_run']
                ? 'Repository ist bereit für eine Update-Prüfung.'
                : 'Update aktuell nicht möglich. Arbeitsverzeichnis ist nicht sauber oder der Status konnte nicht bestimmt werden.';

            return $status;
        }

        $fetch = $this->commands->run([$gitBinary, '-C', $repoPath, 'fetch', '--all', '--prune']);
        if (!$fetch['success']) {
            $message = trim($fetch['stdout'] . "\n" . $fetch['stderr']);
            $status['message'] = 'Remote-Status konnte nicht geprüft werden.' . ($message !== '' ? ' ' . $message : '');

            return $status;
        }

        $status['remote_checked'] = true;

        $remoteCommitProbe = $this->commands->run([$gitBinary, '-C', $repoPath, 'rev-parse', 'origin/' . $branch]);
        if (!$remoteCommitProbe['success']) {
            $message = trim($remoteCommitProbe['stdout'] . "\n" . $remoteCommitProbe['stderr']);
            $status['message'] = 'Remote-Branch origin/' . $branch . ' konnte nicht gelesen werden.' . ($message !== '' ? ' ' . $message : '');

            return $status;
        }

        $status['remote_commit'] = trim($remoteCommitProbe['stdout']);

        $compareProbe = $this->commands->run([$gitBinary, '-C', $repoPath, 'rev-list', '--left-right', '--count', 'HEAD...origin/' . $branch]);
        if (!$compareProbe['success']) {
            $message = trim($compareProbe['stdout'] . "\n" . $compareProbe['stderr']);
            $status['message'] = 'Update-Abstand zu origin/' . $branch . ' konnte nicht bestimmt werden.' . ($message !== '' ? ' ' . $message : '');

            return $status;
        }

        [$ahead, $behind] = array_pad(preg_split('/\s+/', trim($compareProbe['stdout'])) ?: [], 2, '0');
        $status['commits_ahead'] = (int) $ahead;
        $status['commits_behind'] = (int) $behind;
        $status['update_available'] = $status['commits_behind'] > 0;

        $remoteVersionProbe = $this->commands->run([$gitBinary, '-C', $repoPath, 'show', 'origin/' . $branch . ':package.json']);
        if ($remoteVersionProbe['success']) {
            $status['remote_version'] = $this->versions->fromPackageJsonContents($remoteVersionProbe['stdout']);
        }

        $status['can_run'] = $status['update_available'] && $status['worktree_clean'] === true;

        if ($status['update_available'] && $status['worktree_clean'] === true) {
            $status['message'] = 'Ein neues Update ist verfügbar und kann installiert werden.';
        } elseif ($status['update_available']) {
            $status['message'] = 'Ein neues Update ist verfügbar, aber das Arbeitsverzeichnis ist nicht sauber.';
        } elseif ($status['remote_checked']) {
            $status['message'] = 'Es ist kein neues Update verfügbar.';
        } else {
            $status['message'] = 'Remote-Status konnte nicht bestimmt werden.';
        }

        return $status;
    }

    public function runStreaming(callable $emit, ?int $userId = null, ?string $ipAddress = null): array
    {
        $status = $this->status(true);

        if (!$status['enabled']) {
            throw new RuntimeException('Update-Funktion ist deaktiviert.');
        }

        if (!$status['is_repo'] || empty($status['git_binary'])) {
            throw new RuntimeException('Git-Repository ist nicht verfügbar.');
        }

        if ($status['worktree_clean'] !== true) {
            throw new RuntimeException('Update wurde abgebrochen, weil das Arbeitsverzeichnis nicht sauber ist.');
        }

        if ($status['update_in_progress']) {
            throw new RuntimeException($status['message']);
        }

        if ($status['remote_checked'] !== true) {
            throw new RuntimeException($status['message']);
        }

        if (!$status['update_available']) {
            throw new RuntimeException($status['message']);
        }

        $git = (string) $status['git_binary'];
        $repoPath = (string) $status['repo_path'];
        $branch = (string) $status['branch'];
        $beforeVersion = $this->versions->current();
        $lock = $this->acquireRunLock();

        $emit([
            'type' => 'status',
            'state' => 'running',
            'message' => 'Update-Lauf gestartet.',
        ]);
        $emit([
            'type' => 'line',
            'stream' => 'system',
            'text' => 'Lokale Version: v' . $beforeVersion . PHP_EOL,
        ]);

        if (!empty($status['remote_version'])) {
            $emit([
                'type' => 'line',
                'stream' => 'system',
                'text' => 'Remote-Version: v' . $status['remote_version'] . PHP_EOL,
            ]);
        }

        try {
            $emit([
                'type' => 'line',
                'stream' => 'system',
                'text' => PHP_EOL . '$ git fetch --all --prune' . PHP_EOL,
            ]);

            $fetch = $this->commands->runStreaming(
                [$git, '-C', $repoPath, 'fetch', '--all', '--prune'],
                function (string $stream, string $chunk) use ($emit): void {
                    $emit([
                        'type' => 'line',
                        'stream' => $stream,
                        'text' => $chunk,
                    ]);
                }
            );

            if (!$fetch['success']) {
                $message = trim($fetch['stdout'] . "\n" . $fetch['stderr']);
                $this->logger->log('update_failed', 'Git-Fetch fehlgeschlagen.', [
                    'output' => $message,
                    'before_version' => $beforeVersion,
                ], $userId, 'error', $ipAddress);
                throw new RuntimeException('Git-Fetch fehlgeschlagen.' . ($message !== '' ? ' ' . $message : ''));
            }

            $emit([
                'type' => 'line',
                'stream' => 'system',
                'text' => PHP_EOL . '$ git pull --ff-only origin ' . $branch . PHP_EOL,
            ]);

            $pull = $this->commands->runStreaming(
                [$git, '-C', $repoPath, 'pull', '--ff-only', 'origin', $branch],
                function (string $stream, string $chunk) use ($emit): void {
                    $emit([
                        'type' => 'line',
                        'stream' => $stream,
                        'text' => $chunk,
                    ]);
                }
            );

            $output = trim($pull['stdout'] . "\n" . $pull['stderr']);

            if (!$pull['success']) {
                $this->logger->log('update_failed', 'Git-Pull fehlgeschlagen.', [
                    'output' => $output,
                    'before_version' => $beforeVersion,
                ], $userId, 'error', $ipAddress);
                throw new RuntimeException('Git-Pull fehlgeschlagen.' . ($output !== '' ? ' ' . $output : ''));
            }

            $afterVersion = $this->versions->current();
            $versionChanged = $beforeVersion !== $afterVersion;

            $this->logger->log('update_run', 'Update erfolgreich ausgeführt.', [
                'output' => $output,
                'before_version' => $beforeVersion,
                'after_version' => $afterVersion,
                'version_changed' => $versionChanged,
            ], $userId, 'info', $ipAddress);

            $emit([
                'type' => 'result',
                'success' => true,
                'message' => $versionChanged
                    ? 'Update erfolgreich. Version v' . $beforeVersion . ' wurde auf v' . $afterVersion . ' aktualisiert.'
                    : 'Update erfolgreich. Die Version bleibt bei v' . $afterVersion . '.',
                'before_version' => $beforeVersion,
                'after_version' => $afterVersion,
                'version_changed' => $versionChanged,
            ]);

            return [
                'fetch' => $fetch,
                'pull' => $pull,
                'output' => $output,
                'before_version' => $beforeVersion,
                'after_version' => $afterVersion,
                'version_changed' => $versionChanged,
            ];
        } finally {
            $this->releaseRunLock($lock);
        }
    }

    private function isUpdateInProgress(): bool
    {
        $lockPath = $this->lockPath();
        $handle = fopen($lockPath, 'c+');

        if ($handle === false) {
            return false;
        }

        $locked = !flock($handle, LOCK_EX | LOCK_NB);

        if (!$locked) {
            flock($handle, LOCK_UN);
        }

        fclose($handle);

        return $locked;
    }

    private function acquireRunLock()
    {
        $lockPath = $this->lockPath();
        $handle = fopen($lockPath, 'c+');

        if ($handle === false) {
            throw new RuntimeException('Update-Sperrdatei konnte nicht geöffnet werden.');
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new RuntimeException('Ein anderer Update-Lauf ist bereits aktiv.');
        }

        ftruncate($handle, 0);
        fwrite($handle, json_encode([
            'pid' => getmypid(),
            'started_at' => date('c'),
        ], JSON_UNESCAPED_SLASHES));
        fflush($handle);

        return $handle;
    }

    private function releaseRunLock($handle): void
    {
        if (!is_resource($handle)) {
            return;
        }

        ftruncate($handle, 0);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function lockPath(): string
    {
        return rtrim((string) ($this->config['paths']['storage'] ?? ''), '/') . '/cache/' . self::LOCK_FILENAME;
    }
}
