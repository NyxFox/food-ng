<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class UpdateService
{
    public function __construct(
        private readonly array $config,
        private readonly CommandRunner $commands,
        private readonly LoggerService $logger,
        private readonly VersionService $versions
    ) {
    }

    public function status(): array
    {
        $enabled = (bool) ($this->config['features']['update_runner'] ?? false);
        $repoPath = (string) ($this->config['updates']['repo_path'] ?? $this->config['paths']['root']);
        $branch = (string) ($this->config['updates']['branch'] ?? 'main');
        $gitBinary = $this->commands->findBinary(['git'], $this->config['commands']['git'] ?? null);

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

        $branchProbe = $this->commands->run([$gitBinary, '-C', $repoPath, 'rev-parse', '--abbrev-ref', 'HEAD']);
        if ($branchProbe['success']) {
            $status['current_branch'] = trim($branchProbe['stdout']);
        }

        $cleanProbe = $this->commands->run([$gitBinary, '-C', $repoPath, 'status', '--short']);
        if ($cleanProbe['success']) {
            $status['worktree_clean'] = trim($cleanProbe['stdout']) === '';
        }

        $status['can_run'] = $status['is_repo'] && $status['worktree_clean'] === true;
        $status['message'] = $status['can_run']
            ? 'Update kann ausgelöst werden.'
            : 'Update aktuell nicht möglich. Arbeitsverzeichnis ist nicht sauber oder der Status konnte nicht bestimmt werden.';

        return $status;
    }

    public function run(?int $userId = null, ?string $ipAddress = null): array
    {
        $status = $this->status();

        if (!$status['enabled']) {
            throw new RuntimeException('Update-Funktion ist deaktiviert.');
        }

        if (!$status['is_repo'] || empty($status['git_binary'])) {
            throw new RuntimeException('Git-Repository ist nicht verfügbar.');
        }

        if ($status['worktree_clean'] !== true) {
            throw new RuntimeException('Update wurde abgebrochen, weil das Arbeitsverzeichnis nicht sauber ist.');
        }

        $git = (string) $status['git_binary'];
        $repoPath = (string) $status['repo_path'];
        $branch = (string) $status['branch'];
        $beforeVersion = $this->versions->current();

        $fetch = $this->commands->run([$git, '-C', $repoPath, 'fetch', '--all', '--prune']);
        if (!$fetch['success']) {
            $message = trim($fetch['stdout'] . "\n" . $fetch['stderr']);
            $this->logger->log('update_failed', 'Git-Fetch fehlgeschlagen.', [
                'output' => $message,
                'before_version' => $beforeVersion,
            ], $userId, 'error', $ipAddress);
            throw new RuntimeException('Git-Fetch fehlgeschlagen.' . ($message !== '' ? ' ' . $message : ''));
        }

        $pull = $this->commands->run([$git, '-C', $repoPath, 'pull', '--ff-only', 'origin', $branch]);
        $output = trim($pull['stdout'] . "\n" . $pull['stderr']);

        if (!$pull['success']) {
            $this->logger->log('update_failed', 'Git-Pull fehlgeschlagen.', [
                'output' => $output,
                'before_version' => $beforeVersion,
            ], $userId, 'error', $ipAddress);
            throw new RuntimeException('Git-Pull fehlgeschlagen.' . ($output !== '' ? ' ' . $output : ''));
        }

        $afterVersion = $this->versions->current();

        $this->logger->log('update_run', 'Update erfolgreich ausgeführt.', [
            'output' => $output,
            'before_version' => $beforeVersion,
            'after_version' => $afterVersion,
            'version_changed' => $beforeVersion !== $afterVersion,
        ], $userId, 'info', $ipAddress);

        return [
            'fetch' => $fetch,
            'pull' => $pull,
            'output' => $output,
            'before_version' => $beforeVersion,
            'after_version' => $afterVersion,
            'version_changed' => $beforeVersion !== $afterVersion,
        ];
    }
}
