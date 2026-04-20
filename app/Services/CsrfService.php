<?php

declare(strict_types=1);

namespace App\Services;

final class CsrfService
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(private readonly string $fieldName)
    {
    }

    public function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public function validate(string $token): bool
    {
        return hash_equals($this->token(), $token);
    }

    public function fieldName(): string
    {
        return $this->fieldName;
    }
}
