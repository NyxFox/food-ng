<?php

declare(strict_types=1);

echo "PHP-Version: " . PHP_VERSION . PHP_EOL;
echo 'OS-Familie: ' . PHP_OS_FAMILY . PHP_EOL;
echo 'PDO SQLite: ' . (extension_loaded('pdo_sqlite') ? 'ja' : 'nein') . PHP_EOL;
echo 'Fileinfo: ' . (extension_loaded('fileinfo') ? 'ja' : 'nein') . PHP_EOL;
echo 'OpenSSL: ' . (extension_loaded('openssl') ? 'ja' : 'nein') . PHP_EOL;
echo 'proc_open: ' . (function_exists('proc_open') ? 'ja' : 'nein') . PHP_EOL;

$commands = ['qpdf', 'soffice', 'git'];

foreach ($commands as $command) {
    $output = PHP_OS_FAMILY === 'Windows'
        ? shell_exec('where.exe ' . escapeshellarg($command) . ' 2>NUL')
        : shell_exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null');

    echo str_pad($command, 12) . ': ' . (trim((string) $output) !== '' ? trim((string) $output) : 'nicht gefunden') . PHP_EOL;
}
