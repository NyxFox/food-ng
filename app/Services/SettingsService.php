<?php

declare(strict_types=1);

namespace App\Services;

final class SettingsService
{
    private ?array $cache = null;

    public function __construct(
        private readonly Database $db,
        private readonly array $config
    ) {
    }

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $settings = $this->config['defaults']['settings'] ?? [];
        $rows = $this->db->fetchAll('SELECT key, value FROM settings');

        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }

        $this->cache = $settings;

        return $this->cache;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $settings = $this->all();

        return isset($settings[$key]) ? (string) $settings[$key] : $default;
    }

    public function save(array $values, ?int $userId = null): void
    {
        $now = date('c');

        $this->db->transaction(function () use ($values, $userId, $now): void {
            foreach ($values as $key => $value) {
                $this->db->execute(
                    'INSERT INTO settings (key, value, updated_at, updated_by)
                     VALUES (:key, :value, :updated_at, :updated_by)
                     ON CONFLICT(key) DO UPDATE SET
                        value = excluded.value,
                        updated_at = excluded.updated_at,
                        updated_by = excluded.updated_by',
                    [
                        'key' => (string) $key,
                        'value' => (string) $value,
                        'updated_at' => $now,
                        'updated_by' => $userId,
                    ]
                );
            }
        });

        $this->cache = null;
    }
}
