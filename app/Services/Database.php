<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

final class Database
{
    private PDO $pdo;

    public function __construct(string $databasePath)
    {
        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_writable($directory)) {
            throw new RuntimeException(
                'Das Storage-Verzeichnis für SQLite ist nicht beschreibbar: ' . $directory
            );
        }

        if (!is_file($databasePath)) {
            touch($databasePath);
            @chmod($databasePath, 0664);
        }

        if (!is_writable($databasePath)) {
            throw new RuntimeException(
                'Die SQLite-Datei ist nicht beschreibbar: ' . $databasePath
                . '. Häufige Ursache: eine zuvor per Docker als root angelegte Datei. '
                . 'Bitte Datei löschen oder Besitz/Schreibrechte korrigieren.'
            );
        }

        $this->pdo = new PDO('sqlite:' . $databasePath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON;');
        $this->pdo->exec('PRAGMA busy_timeout = 5000;');
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $parameters = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);

        foreach ($parameters as $key => $value) {
            $param = is_int($key) ? $key + 1 : ':' . ltrim((string) $key, ':');
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };

            $statement->bindValue($param, $value, $type);
        }

        $statement->execute();

        return $statement;
    }

    public function execute(string $sql, array $parameters = []): void
    {
        $this->query($sql, $parameters);
    }

    public function fetchAll(string $sql, array $parameters = []): array
    {
        return $this->query($sql, $parameters)->fetchAll();
    }

    public function fetchOne(string $sql, array $parameters = []): ?array
    {
        $result = $this->query($sql, $parameters)->fetch();

        return $result === false ? null : $result;
    }

    public function fetchValue(string $sql, array $parameters = []): mixed
    {
        return $this->query($sql, $parameters)->fetchColumn();
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback($this);
            $this->pdo->commit();

            return $result;
        } catch (Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $throwable;
        }
    }
}
