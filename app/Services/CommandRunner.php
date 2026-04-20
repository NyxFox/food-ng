<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class CommandRunner
{
    public function __construct(private readonly array $config)
    {
    }

    public function canRunCommands(): bool
    {
        return function_exists('proc_open');
    }

    public function run(array $command, ?string $cwd = null): array
    {
        if (!$this->canRunCommands()) {
            throw new RuntimeException('Der Server erlaubt keine externen Prozessaufrufe (proc_open deaktiviert).');
        }

        $shellCommand = implode(' ', array_map(static fn (string $part): string => escapeshellarg($part), $command));
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($shellCommand, $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            throw new RuntimeException('Externer Prozess konnte nicht gestartet werden.');
        }

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'stdout' => trim((string) $stdout),
            'stderr' => trim((string) $stderr),
            'command' => $shellCommand,
        ];
    }

    public function runStreaming(array $command, callable $onChunk, ?string $cwd = null): array
    {
        if (!$this->canRunCommands()) {
            throw new RuntimeException('Der Server erlaubt keine externen Prozessaufrufe (proc_open deaktiviert).');
        }

        $shellCommand = implode(' ', array_map(static fn (string $part): string => escapeshellarg($part), $command));
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($shellCommand, $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            throw new RuntimeException('Externer Prozess konnte nicht gestartet werden.');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';

        try {
            while (true) {
                $stdoutChunk = stream_get_contents($pipes[1]);
                if (is_string($stdoutChunk) && $stdoutChunk !== '') {
                    $stdout .= $stdoutChunk;
                    $onChunk('stdout', $stdoutChunk);
                }

                $stderrChunk = stream_get_contents($pipes[2]);
                if (is_string($stderrChunk) && $stderrChunk !== '') {
                    $stderr .= $stderrChunk;
                    $onChunk('stderr', $stderrChunk);
                }

                $status = proc_get_status($process);

                if (!$status['running']) {
                    break;
                }

                usleep(100000);
            }

            foreach ([1 => 'stdout', 2 => 'stderr'] as $index => $streamName) {
                $remaining = stream_get_contents($pipes[$index]);

                if (!is_string($remaining) || $remaining === '') {
                    continue;
                }

                if ($streamName === 'stdout') {
                    $stdout .= $remaining;
                } else {
                    $stderr .= $remaining;
                }

                $onChunk($streamName, $remaining);
            }
        } finally {
            fclose($pipes[1]);
            fclose($pipes[2]);
        }

        $exitCode = proc_close($process);

        return [
            'success' => $exitCode === 0,
            'exit_code' => $exitCode,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
            'command' => $shellCommand,
        ];
    }

    public function findBinary(array|string $candidates, ?string $override = null): ?string
    {
        if (!empty($override)) {
            return $override;
        }

        foreach ((array) $candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            if (str_contains($candidate, DIRECTORY_SEPARATOR) && file_exists($candidate)) {
                return $candidate;
            }

            try {
                $result = PHP_OS_FAMILY === 'Windows'
                    ? $this->run(['cmd', '/c', 'where ' . $candidate])
                    : $this->run(['sh', '-lc', 'command -v ' . $candidate]);
            } catch (RuntimeException) {
                return null;
            }

            if ($result['success'] && $result['stdout'] !== '') {
                return trim(explode("\n", $result['stdout'])[0]);
            }
        }

        return null;
    }
}
