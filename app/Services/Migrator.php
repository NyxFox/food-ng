<?php

declare(strict_types=1);

namespace App\Services;

final class Migrator
{
    public function __construct(
        private readonly Database $db,
        private readonly array $config
    ) {
    }

    public function migrate(): void
    {
        $schema = file_get_contents($this->config['paths']['root'] . '/database/schema.sql');
        $this->db->pdo()->exec((string) $schema);

        $this->seedDefaultSettings();
        $this->seedDefaultAdmin();
    }

    private function seedDefaultSettings(): void
    {
        $defaults = $this->config['defaults']['settings'] ?? [];
        $now = date('c');

        foreach ($defaults as $key => $value) {
            $this->db->execute(
                'INSERT OR IGNORE INTO settings (key, value, updated_at, updated_by) VALUES (:key, :value, :updated_at, NULL)',
                [
                    'key' => (string) $key,
                    'value' => (string) $value,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function seedDefaultAdmin(): void
    {
        if (!(bool) ($this->config['setup']['auto_create_default_admin'] ?? false)) {
            return;
        }

        $count = (int) $this->db->fetchValue('SELECT COUNT(*) FROM users');

        if ($count > 0) {
            return;
        }

        $now = date('c');
        $username = (string) $this->config['setup']['default_admin_username'];
        $displayName = (string) $this->config['setup']['default_admin_display_name'];
        $password = (string) $this->config['setup']['default_admin_password'];
        $role = (string) $this->config['setup']['default_admin_role'];

        $this->db->execute(
            'INSERT INTO users (username, display_name, password_hash, role, is_active, last_login_at, created_at, updated_at)
             VALUES (:username, :display_name, :password_hash, :role, 1, NULL, :created_at, :updated_at)',
            [
                'username' => $username,
                'display_name' => $displayName,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $this->db->execute(
            'INSERT INTO logs (level, action, message, context_json, user_id, ip_address, created_at)
             VALUES (:level, :action, :message, :context_json, NULL, NULL, :created_at)',
            [
                'level' => 'info',
                'action' => 'bootstrap',
                'message' => 'Standard-Administrator wurde automatisch angelegt.',
                'context_json' => json_encode(['username' => $username], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
            ]
        );
    }
}
