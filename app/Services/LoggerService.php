<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;

final class LoggerService
{
    public function __construct(
        private readonly Database $db,
        private readonly string $logFile
    ) {
    }

    public function log(
        string $action,
        string $message,
        array $context = [],
        ?int $userId = null,
        string $level = 'info',
        ?string $ipAddress = null
    ): void {
        $now = date('c');
        $contextJson = $context === [] ? null : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $this->db->execute(
                'INSERT INTO logs (level, action, message, context_json, user_id, ip_address, created_at)
                 VALUES (:level, :action, :message, :context_json, :user_id, :ip_address, :created_at)',
                [
                    'level' => $level,
                    'action' => $action,
                    'message' => $message,
                    'context_json' => $contextJson,
                    'user_id' => $userId,
                    'ip_address' => $ipAddress,
                    'created_at' => $now,
                ]
            );
        } catch (Throwable) {
        }

        $line = json_encode([
            'created_at' => $now,
            'level' => $level,
            'action' => $action,
            'message' => $message,
            'context' => $context,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($line !== false) {
            @file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    public function latest(array $filters = [], int $limit = 200): array
    {
        $sql = 'SELECT logs.*, users.username
                FROM logs
                LEFT JOIN users ON users.id = logs.user_id
                WHERE 1 = 1';
        $parameters = [];

        if (!empty($filters['level'])) {
            $sql .= ' AND logs.level = :level';
            $parameters['level'] = (string) $filters['level'];
        }

        if (!empty($filters['action'])) {
            $sql .= ' AND logs.action = :action';
            $parameters['action'] = (string) $filters['action'];
        }

        if (!empty($filters['query'])) {
            $sql .= ' AND (logs.message LIKE :query OR logs.context_json LIKE :query)';
            $parameters['query'] = '%' . (string) $filters['query'] . '%';
        }

        $sql .= ' ORDER BY logs.id DESC LIMIT :limit';
        $parameters['limit'] = $limit;

        return $this->db->fetchAll($sql, $parameters);
    }
}
