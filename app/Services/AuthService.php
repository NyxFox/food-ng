<?php

declare(strict_types=1);

namespace App\Services;

final class AuthService
{
    private const SESSION_KEY = '_auth_user_id';

    private ?array $cachedUser = null;

    public function __construct(
        private readonly Database $db,
        private readonly LoggerService $logger,
        private readonly FlashService $flash
    ) {
    }

    public function attempt(string $username, string $password, ?string $ipAddress = null): bool
    {
        $username = trim($username);
        $user = $this->db->fetchOne(
            'SELECT * FROM users WHERE username = :username AND is_active = 1 LIMIT 1',
            ['username' => $username]
        );

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            $this->logger->log(
                'login_failed',
                'Anmeldung fehlgeschlagen.',
                ['username' => $username],
                null,
                'warning',
                $ipAddress
            );

            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::SESSION_KEY] = (int) $user['id'];
        $this->cachedUser = null;

        $this->db->execute(
            'UPDATE users SET last_login_at = :last_login_at, updated_at = :updated_at WHERE id = :id',
            [
                'last_login_at' => date('c'),
                'updated_at' => date('c'),
                'id' => (int) $user['id'],
            ]
        );

        $this->logger->log(
            'login',
            'Benutzer angemeldet.',
            ['username' => $user['username']],
            (int) $user['id'],
            'info',
            $ipAddress
        );

        return true;
    }

    public function logout(?string $ipAddress = null): void
    {
        $user = $this->user();

        unset($_SESSION[self::SESSION_KEY]);
        $this->cachedUser = null;
        session_regenerate_id(true);

        if ($user !== null) {
            $this->logger->log(
                'logout',
                'Benutzer abgemeldet.',
                ['username' => $user['username']],
                (int) $user['id'],
                'info',
                $ipAddress
            );
        }
    }

    public function user(): ?array
    {
        if ($this->cachedUser !== null) {
            return $this->cachedUser;
        }

        $userId = $_SESSION[self::SESSION_KEY] ?? null;

        if ($userId === null) {
            return null;
        }

        $user = $this->db->fetchOne(
            'SELECT id, username, display_name, role, is_active, last_login_at, created_at, updated_at
             FROM users WHERE id = :id AND is_active = 1 LIMIT 1',
            ['id' => (int) $userId]
        );

        if ($user === null) {
            unset($_SESSION[self::SESSION_KEY]);

            return null;
        }

        $this->cachedUser = $user;

        return $this->cachedUser;
    }

    public function id(): ?int
    {
        $user = $this->user();

        return $user === null ? null : (int) $user['id'];
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function isAdmin(): bool
    {
        return ($this->user()['role'] ?? null) === 'admin';
    }
}
