<?php

declare(strict_types=1);

namespace App\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class MealPlanService
{
    public function __construct(
        private readonly Database $db,
        private readonly LoggerService $logger,
        private readonly DocumentProcessor $documents,
        private readonly array $config
    ) {
    }

    public function listAll(): array
    {
        return $this->db->fetchAll(
            'SELECT meal_plans.*, users.username, users.display_name
             FROM meal_plans
             LEFT JOIN users ON users.id = meal_plans.created_by
             ORDER BY meal_plans.is_active DESC, meal_plans.created_at DESC'
        );
    }

    public function summary(): array
    {
        return [
            'total' => (int) $this->db->fetchValue('SELECT COUNT(*) FROM meal_plans'),
            'draft' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM meal_plans WHERE status = 'draft'"),
            'active' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM meal_plans WHERE status = 'active'"),
            'archived' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM meal_plans WHERE status = 'archived'"),
            'active_plan' => $this->active(),
        ];
    }

    public function active(): ?array
    {
        return $this->db->fetchOne(
            'SELECT meal_plans.*, users.username, users.display_name
             FROM meal_plans
             LEFT JOIN users ON users.id = meal_plans.created_by
             WHERE meal_plans.is_active = 1
             ORDER BY meal_plans.id DESC
             LIMIT 1'
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT meal_plans.*, users.username, users.display_name
             FROM meal_plans
             LEFT JOIN users ON users.id = meal_plans.created_by
             WHERE meal_plans.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function createFromUpload(
        string $title,
        array $normalFile,
        array $vegetarianFile,
        int $userId,
        ?string $ipAddress = null
    ): int {
        $title = trim($title);

        if ($title === '' || mb_strlen($title) < 3) {
            throw new RuntimeException('Bitte einen aussagekräftigen Titel angeben.');
        }

        $status = $this->documents->status();

        if (!$status['merge_available']) {
            throw new RuntimeException('PDF-Zusammenführung ist auf diesem System nicht verfügbar.');
        }

        $batch = date('YmdHis') . '-' . bin2hex(random_bytes(4));
        $storageRoot = rtrim((string) $this->config['paths']['storage'], '/');
        $uploadDir = $storageRoot . '/uploads/meal-plans/' . $batch;
        $generatedDir = $storageRoot . '/generated/meal-plans/' . $batch;

        $normalMeta = $this->documents->inspectUpload($normalFile);
        $vegetarianMeta = $this->documents->inspectUpload($vegetarianFile);

        $normalOriginalPath = $uploadDir . '/normal-original.' . $normalMeta['extension'];
        $vegetarianOriginalPath = $uploadDir . '/vegetarian-original.' . $vegetarianMeta['extension'];
        $normalPdfPath = $generatedDir . '/normal.pdf';
        $vegetarianPdfPath = $generatedDir . '/vegetarian.pdf';
        $mergedPdfPath = $generatedDir . '/merged.pdf';

        $storedPaths = [];
        $notes = [];

        try {
            $storedNormal = $this->documents->storeUpload($normalFile, $normalOriginalPath);
            $storedVegetarian = $this->documents->storeUpload($vegetarianFile, $vegetarianOriginalPath);

            $storedPaths[] = $storedNormal['path'];
            $storedPaths[] = $storedVegetarian['path'];

            $normalPdf = $this->documents->ensurePdf($storedNormal['path'], $storedNormal['extension'], $normalPdfPath);
            $vegetarianPdf = $this->documents->ensurePdf($storedVegetarian['path'], $storedVegetarian['extension'], $vegetarianPdfPath);

            $storedPaths[] = $normalPdf['pdf_path'];
            $storedPaths[] = $vegetarianPdf['pdf_path'];

            $notes[] = $normalPdf['note'];
            $notes[] = $vegetarianPdf['note'];

            $this->documents->ensureSinglePagePdf($normalPdf['pdf_path'], 'Normaler Speiseplan');
            $this->documents->ensureSinglePagePdf($vegetarianPdf['pdf_path'], 'Vegetarischer Speiseplan');
            $this->documents->mergeTwoPdfs($normalPdf['pdf_path'], $vegetarianPdf['pdf_path'], $mergedPdfPath);

            $storedPaths[] = $mergedPdfPath;

            $createdAt = date('c');

            $planId = $this->db->transaction(function () use (
                $title,
                $normalMeta,
                $vegetarianMeta,
                $normalOriginalPath,
                $vegetarianOriginalPath,
                $mergedPdfPath,
                $createdAt,
                $userId,
                $notes
            ): int {
                $this->db->execute(
                    'INSERT INTO meal_plans (
                        title,
                        original_normal_filename,
                        original_vegetarian_filename,
                        normal_source_type,
                        vegetarian_source_type,
                        normal_storage_path,
                        vegetarian_storage_path,
                        merged_pdf_path,
                        preview_status,
                        status,
                        is_active,
                        conversion_notes,
                        created_at,
                        updated_at,
                        created_by
                    ) VALUES (
                        :title,
                        :original_normal_filename,
                        :original_vegetarian_filename,
                        :normal_source_type,
                        :vegetarian_source_type,
                        :normal_storage_path,
                        :vegetarian_storage_path,
                        :merged_pdf_path,
                        :preview_status,
                        :status,
                        0,
                        :conversion_notes,
                        :created_at,
                        :updated_at,
                        :created_by
                    )',
                    [
                        'title' => $title,
                        'original_normal_filename' => $normalMeta['original_name'],
                        'original_vegetarian_filename' => $vegetarianMeta['original_name'],
                        'normal_source_type' => $normalMeta['extension'],
                        'vegetarian_source_type' => $vegetarianMeta['extension'],
                        'normal_storage_path' => $this->relativeStoragePath($normalOriginalPath),
                        'vegetarian_storage_path' => $this->relativeStoragePath($vegetarianOriginalPath),
                        'merged_pdf_path' => $this->relativeStoragePath($mergedPdfPath),
                        'preview_status' => 'pending',
                        'status' => 'draft',
                        'conversion_notes' => implode(' ', array_filter($notes)),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                        'created_by' => $userId,
                    ]
                );

                return $this->db->lastInsertId();
            });

            $this->logger->log(
                'meal_plan_upload',
                'Neuer Speiseplan hochgeladen.',
                [
                    'meal_plan_id' => $planId,
                    'title' => $title,
                    'normal_type' => $normalMeta['extension'],
                    'vegetarian_type' => $vegetarianMeta['extension'],
                ],
                $userId,
                'info',
                $ipAddress
            );

            return $planId;
        } catch (RuntimeException $exception) {
            $this->cleanupDirectories([$uploadDir, $generatedDir]);

            $this->logger->log(
                'meal_plan_upload_failed',
                'Speiseplan-Upload fehlgeschlagen.',
                [
                    'title' => $title,
                    'error' => $exception->getMessage(),
                ],
                $userId,
                'error',
                $ipAddress
            );

            throw $exception;
        }
    }

    public function activate(int $id, int $userId, ?string $ipAddress = null): void
    {
        $plan = $this->find($id);

        if ($plan === null) {
            throw new RuntimeException('Der Speiseplan wurde nicht gefunden.');
        }

        $now = date('c');

        $this->db->transaction(function () use ($id, $now): void {
            $this->db->execute(
                "UPDATE meal_plans
                 SET is_active = 0,
                     status = CASE WHEN status = 'active' THEN 'archived' ELSE status END,
                     updated_at = :updated_at
                 WHERE is_active = 1",
                ['updated_at' => $now]
            );

            $this->db->execute(
                "UPDATE meal_plans
                 SET is_active = 1,
                     status = 'active',
                     preview_status = 'approved',
                     updated_at = :updated_at
                 WHERE id = :id",
                [
                    'updated_at' => $now,
                    'id' => $id,
                ]
            );
        });

        $this->logger->log(
            'meal_plan_activated',
            'Speiseplan aktiviert.',
            ['meal_plan_id' => $id, 'title' => $plan['title']],
            $userId,
            'info',
            $ipAddress
        );
    }

    public function archive(int $id, int $userId, ?string $ipAddress = null): void
    {
        $plan = $this->find($id);

        if ($plan === null) {
            throw new RuntimeException('Der Speiseplan wurde nicht gefunden.');
        }

        $this->db->execute(
            "UPDATE meal_plans
             SET status = 'archived',
                 preview_status = CASE WHEN preview_status = 'pending' THEN 'rejected' ELSE preview_status END,
                 is_active = 0,
                 updated_at = :updated_at
             WHERE id = :id",
            [
                'updated_at' => date('c'),
                'id' => $id,
            ]
        );

        $this->logger->log(
            'meal_plan_archived',
            'Speiseplan archiviert.',
            ['meal_plan_id' => $id, 'title' => $plan['title']],
            $userId,
            'info',
            $ipAddress
        );
    }

    public function delete(int $id, int $userId, ?string $ipAddress = null): void
    {
        $plan = $this->find($id);

        if ($plan === null) {
            throw new RuntimeException('Der Speiseplan wurde nicht gefunden.');
        }

        if ((int) $plan['is_active'] === 1) {
            throw new RuntimeException('Der aktuell aktive Speiseplan kann nicht direkt gelöscht werden.');
        }

        $this->db->execute('DELETE FROM meal_plans WHERE id = :id', ['id' => $id]);

        $paths = [
            dirname($this->absoluteStoragePath((string) $plan['normal_storage_path'])),
            dirname($this->absoluteStoragePath((string) $plan['merged_pdf_path'])),
        ];
        $this->cleanupDirectories($paths);

        $this->logger->log(
            'meal_plan_deleted',
            'Speiseplan gelöscht.',
            ['meal_plan_id' => $id, 'title' => $plan['title']],
            $userId,
            'warning',
            $ipAddress
        );
    }

    public function absoluteStoragePath(string $relativePath): string
    {
        return rtrim((string) $this->config['paths']['storage'], '/') . '/' . ltrim($relativePath, '/');
    }

    public function downloadFilename(array $plan): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $plan['title']) ?: 'speiseplan';

        return strtolower(trim($slug, '-')) . '.pdf';
    }

    private function relativeStoragePath(string $absolutePath): string
    {
        $storageRoot = rtrim((string) $this->config['paths']['storage'], '/');
        $relative = ltrim(str_replace($storageRoot, '', $absolutePath), '/');

        return str_replace('\\', '/', $relative);
    }

    private function cleanupDirectories(array $directories): void
    {
        foreach (array_unique($directories) as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    @rmdir($item->getPathname());
                } else {
                    @unlink($item->getPathname());
                }
            }

            @rmdir($directory);
        }
    }
}
