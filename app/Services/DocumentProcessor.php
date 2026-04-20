<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class DocumentProcessor
{
    private ?array $statusCache = null;

    public function __construct(
        private readonly array $config,
        private readonly CommandRunner $commands
    ) {
    }

    public function status(): array
    {
        if ($this->statusCache !== null) {
            return $this->statusCache;
        }

        $qpdf = $this->commands->findBinary(['qpdf'], $this->config['commands']['qpdf'] ?? null);
        $soffice = $this->commands->findBinary(['soffice', 'libreoffice'], $this->config['commands']['soffice'] ?? null);

        $this->statusCache = [
            'merge_available' => $qpdf !== null,
            'merge_binary' => $qpdf,
            'docx_conversion_available' => $soffice !== null,
            'docx_binary' => $soffice,
            'command_runner_available' => $this->commands->canRunCommands(),
        ];

        return $this->statusCache;
    }

    public function inspectUpload(array $file): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Die Datei ist zu groß.',
                UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur teilweise hochgeladen.',
                UPLOAD_ERR_NO_FILE => 'Bitte eine Datei auswählen.',
                default => 'Datei-Upload fehlgeschlagen.',
            });
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $maxUploadBytes = (int) ($this->config['security']['max_upload_bytes'] ?? 10485760);

        if ($size <= 0 || $size > $maxUploadBytes) {
            throw new RuntimeException('Die Datei überschreitet die erlaubte Größe von ' . number_format($maxUploadBytes / 1024 / 1024, 1, ',', '.') . ' MB.');
        }

        $originalName = (string) ($file['name'] ?? 'upload');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, ['pdf', 'docx'], true)) {
            throw new RuntimeException('Erlaubt sind nur PDF- und DOCX-Dateien.');
        }

        $mime = (string) ((new \finfo(FILEINFO_MIME_TYPE))->file($tmpPath) ?: 'application/octet-stream');

        if ($extension === 'pdf' && !$this->looksLikePdf($tmpPath)) {
            throw new RuntimeException('Die hochgeladene PDF-Datei ist ungültig.');
        }

        if ($extension === 'docx' && !in_array($mime, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/octet-stream',
        ], true)) {
            throw new RuntimeException('Die DOCX-Datei konnte nicht verifiziert werden.');
        }

        return [
            'extension' => $extension,
            'mime' => $mime,
            'original_name' => $originalName,
            'safe_name' => $this->sanitizeOriginalName($originalName, $extension),
        ];
    }

    public function storeUpload(array $file, string $targetPath): array
    {
        $meta = $this->inspectUpload($file);
        $directory = dirname($targetPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $tmpPath = (string) $file['tmp_name'];
        $moved = move_uploaded_file($tmpPath, $targetPath);

        if (!$moved) {
            $moved = @rename($tmpPath, $targetPath) || @copy($tmpPath, $targetPath);
        }

        if (!$moved) {
            throw new RuntimeException('Die hochgeladene Datei konnte nicht gespeichert werden.');
        }

        return $meta + ['path' => $targetPath];
    }

    public function ensurePdf(string $sourcePath, string $sourceType, string $outputPath): array
    {
        if ($sourceType === 'pdf') {
            return [
                'pdf_path' => $sourcePath,
                'note' => 'PDF direkt übernommen.',
            ];
        }

        $status = $this->status();

        if (!$status['docx_conversion_available'] || empty($status['docx_binary'])) {
            throw new RuntimeException('DOCX-Konvertierung ist auf diesem System nicht verfügbar. Bitte LibreOffice/soffice installieren oder PDF-Dateien hochladen.');
        }

        $outputDir = dirname($outputPath);

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $result = $this->commands->run([
            (string) $status['docx_binary'],
            '--headless',
            '--convert-to',
            'pdf',
            '--outdir',
            $outputDir,
            $sourcePath,
        ]);

        $expectedPath = $outputDir . '/' . pathinfo($sourcePath, PATHINFO_FILENAME) . '.pdf';

        if (!$result['success'] || !is_file($expectedPath)) {
            $output = trim($result['stdout'] . "\n" . $result['stderr']);
            throw new RuntimeException('DOCX-Konvertierung fehlgeschlagen.' . ($output !== '' ? ' Ausgabe: ' . $output : ''));
        }

        if ($expectedPath !== $outputPath) {
            @unlink($outputPath);
            rename($expectedPath, $outputPath);
        }

        return [
            'pdf_path' => $outputPath,
            'note' => 'DOCX via LibreOffice in PDF konvertiert.',
        ];
    }

    public function ensureSinglePagePdf(string $pdfPath, string $label): void
    {
        $pageCount = $this->pdfPageCount($pdfPath);

        if ($pageCount !== 1) {
            throw new RuntimeException($label . ' muss genau eine PDF-Seite enthalten. Gefunden: ' . $pageCount . ' Seiten.');
        }
    }

    public function mergeTwoPdfs(string $normalPdf, string $vegetarianPdf, string $outputPath): void
    {
        $status = $this->status();

        if (!$status['merge_available'] || empty($status['merge_binary'])) {
            throw new RuntimeException('PDF-Zusammenführung ist auf diesem System nicht verfügbar.');
        }

        $directory = dirname($outputPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $result = $this->commands->run([
            (string) $status['merge_binary'],
            '--empty',
            '--pages',
            $normalPdf,
            '1-z',
            $vegetarianPdf,
            '1-z',
            '--',
            $outputPath,
        ]);

        if (!$result['success'] || !is_file($outputPath)) {
            $output = trim($result['stdout'] . "\n" . $result['stderr']);
            throw new RuntimeException('Das finale PDF konnte nicht erzeugt werden.' . ($output !== '' ? ' Ausgabe: ' . $output : ''));
        }

        $pageCount = $this->pdfPageCount($outputPath);

        if ($pageCount !== 2) {
            throw new RuntimeException('Das finale PDF muss genau 2 Seiten enthalten. Tatsächlich erzeugt wurden ' . $pageCount . ' Seiten.');
        }
    }

    public function pdfPageCount(string $pdfPath): int
    {
        $status = $this->status();

        if (!$status['merge_available'] || empty($status['merge_binary'])) {
            throw new RuntimeException('Die PDF-Seitenzahl kann ohne qpdf nicht geprüft werden.');
        }

        $result = $this->commands->run([
            (string) $status['merge_binary'],
            '--show-npages',
            $pdfPath,
        ]);

        if (!$result['success']) {
            $output = trim($result['stdout'] . "\n" . $result['stderr']);
            throw new RuntimeException('PDF-Seitenzahl konnte nicht gelesen werden.' . ($output !== '' ? ' Ausgabe: ' . $output : ''));
        }

        return max(0, (int) trim($result['stdout']));
    }

    private function looksLikePdf(string $path): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 5);
        fclose($handle);

        return $header === '%PDF-';
    }

    private function sanitizeOriginalName(string $originalName, string $extension): string
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $sanitized = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $baseName) ?: 'datei';

        return trim($sanitized, '-') . '.' . $extension;
    }
}
