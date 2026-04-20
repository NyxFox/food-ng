<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class UserService
{
    public function __construct(
        private readonly Database $db,
        private readonly LoggerService $logger
    ) {
    }

    public function listAll(): array
    {
        return $this->db->fetchAll('SELECT * FROM users ORDER BY is_active DESC, username ASC');
    }

    public function listPage(int $page = 1, int $perPage = 10): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        return $this->db->fetchAll(
            'SELECT *
             FROM users
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset',
            [
                'limit' => $perPage,
                'offset' => ($page - 1) * $perPage,
            ]
        );
    }

    public function count(): int
    {
        return (int) $this->db->fetchValue('SELECT COUNT(*) FROM users');
    }

    public function find(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE id = :id LIMIT 1', ['id' => $id]);
    }

    public function create(array $data, int $actorId, ?string $ipAddress = null): int
    {
        $username = $this->validateUsername((string) ($data['username'] ?? ''));
        $displayName = $this->validateDisplayName((string) ($data['display_name'] ?? ''));
        $password = $this->validatePassword((string) ($data['password'] ?? ''));
        $role = $this->validateRole((string) ($data['role'] ?? 'editor'));
        $this->ensureUsernameUnique($username);

        $now = date('c');

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

        $userId = $this->db->lastInsertId();

        $this->logger->log(
            'user_created',
            'Benutzer angelegt.',
            ['user_id' => $userId, 'username' => $username, 'role' => $role],
            $actorId,
            'info',
            $ipAddress
        );

        return $userId;
    }

    public function update(int $id, array $data, int $actorId, ?string $ipAddress = null): void
    {
        $user = $this->find($id);

        if ($user === null) {
            throw new RuntimeException('Der Benutzer wurde nicht gefunden.');
        }

        $username = $this->validateUsername((string) ($data['username'] ?? ''));
        $displayName = $this->validateDisplayName((string) ($data['display_name'] ?? ''));
        $role = $this->validateRole((string) ($data['role'] ?? 'editor'));
        $isActive = !empty($data['is_active']) ? 1 : 0;

        if ($id === $actorId && $isActive === 0) {
            throw new RuntimeException('Der aktuell angemeldete Benutzer kann nicht deaktiviert werden.');
        }

        $this->ensureUsernameUnique($username, $id);
        $this->ensureAdminStillExists($id, $role, $isActive);

        $this->db->execute(
            'UPDATE users
             SET username = :username,
                 display_name = :display_name,
                 role = :role,
                 is_active = :is_active,
                 updated_at = :updated_at
             WHERE id = :id',
            [
                'username' => $username,
                'display_name' => $displayName,
                'role' => $role,
                'is_active' => $isActive,
                'updated_at' => date('c'),
                'id' => $id,
            ]
        );

        $this->logger->log(
            'user_updated',
            'Benutzer geändert.',
            ['user_id' => $id, 'username' => $username, 'role' => $role, 'is_active' => $isActive],
            $actorId,
            'info',
            $ipAddress
        );
    }

    public function resetPassword(int $id, string $password, int $actorId, ?string $ipAddress = null): void
    {
        $user = $this->find($id);

        if ($user === null) {
            throw new RuntimeException('Der Benutzer wurde nicht gefunden.');
        }

        $password = $this->validatePassword($password);

        $this->db->execute(
            'UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id',
            [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'updated_at' => date('c'),
                'id' => $id,
            ]
        );

        $this->logger->log(
            'user_password_reset',
            'Passwort neu gesetzt.',
            ['user_id' => $id, 'username' => $user['username']],
            $actorId,
            'warning',
            $ipAddress
        );
    }

    public function toggleActive(int $id, int $actorId, ?string $ipAddress = null): void
    {
        $user = $this->find($id);

        if ($user === null) {
            throw new RuntimeException('Der Benutzer wurde nicht gefunden.');
        }

        if ($id === $actorId) {
            throw new RuntimeException('Der aktuell angemeldete Benutzer kann nicht deaktiviert werden.');
        }

        $newActive = (int) $user['is_active'] === 1 ? 0 : 1;
        $this->ensureAdminStillExists($id, (string) $user['role'], $newActive);

        $this->db->execute(
            'UPDATE users SET is_active = :is_active, updated_at = :updated_at WHERE id = :id',
            [
                'is_active' => $newActive,
                'updated_at' => date('c'),
                'id' => $id,
            ]
        );

        $this->logger->log(
            'user_toggled',
            'Benutzerstatus geändert.',
            ['user_id' => $id, 'username' => $user['username'], 'is_active' => $newActive],
            $actorId,
            'info',
            $ipAddress
        );
    }

    private function ensureUsernameUnique(string $username, ?int $ignoreId = null): void
    {
        $sql = 'SELECT id FROM users WHERE username = :username';
        $parameters = ['username' => $username];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
            $parameters['ignore_id'] = $ignoreId;
        }

        if ($this->db->fetchOne($sql, $parameters) !== null) {
            throw new RuntimeException('Der Benutzername ist bereits vergeben.');
        }
    }

    private function ensureAdminStillExists(int $userId, string $futureRole, int $futureActive): void
    {
        $user = $this->find($userId);

        if ($user === null) {
            return;
        }

        $wasAdmin = (string) $user['role'] === 'admin' && (int) $user['is_active'] === 1;
        $willRemainAdmin = $futureRole === 'admin' && $futureActive === 1;

        if (!$wasAdmin || $willRemainAdmin) {
            return;
        }

        $otherAdmins = (int) $this->db->fetchValue(
            "SELECT COUNT(*) FROM users WHERE id != :id AND role = 'admin' AND is_active = 1",
            ['id' => $userId]
        );

        if ($otherAdmins < 1) {
            throw new RuntimeException('Mindestens ein aktiver Administrator muss erhalten bleiben.');
        }
    }

    private function validateUsername(string $username): string
    {
        $username = trim($username);

        if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
            throw new RuntimeException('Benutzernamen bitte mit 3 bis 50 Zeichen aus Buchstaben, Zahlen, Punkt, Minus oder Unterstrich angeben.');
        }

        return $username;
    }

    private function validateDisplayName(string $displayName): string
    {
        $displayName = trim($displayName);

        if ($displayName === '' || mb_strlen($displayName) > 100) {
            throw new RuntimeException('Der Anzeigename muss zwischen 1 und 100 Zeichen lang sein.');
        }

        return $displayName;
    }

    private function validatePassword(string $password): string
    {
        if (mb_strlen($password) < 8) {
            throw new RuntimeException('Passwörter müssen mindestens 8 Zeichen lang sein.');
        }

        return $password;
    }

    private function validateRole(string $role): string
    {
        if (!in_array($role, ['admin', 'editor'], true)) {
            throw new RuntimeException('Ungültige Rolle.');
        }

        return $role;
    }
}
