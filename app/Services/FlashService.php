<?php

declare(strict_types=1);

namespace App\Services;

final class FlashService
{
    private const FLASH_KEY = '_flash_messages';
    private const OLD_INPUT_KEY = '_old_input';

    public function add(string $type, string $message): void
    {
        $_SESSION[self::FLASH_KEY][] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    public function success(string $message): void
    {
        $this->add('success', $message);
    }

    public function error(string $message): void
    {
        $this->add('error', $message);
    }

    public function warning(string $message): void
    {
        $this->add('warning', $message);
    }

    public function info(string $message): void
    {
        $this->add('info', $message);
    }

    public function consume(): array
    {
        $messages = $_SESSION[self::FLASH_KEY] ?? [];
        unset($_SESSION[self::FLASH_KEY]);

        return is_array($messages) ? $messages : [];
    }

    public function setOldInput(array $input): void
    {
        $_SESSION[self::OLD_INPUT_KEY] = $input;
    }

    public function consumeOldInput(): array
    {
        $old = $_SESSION[self::OLD_INPUT_KEY] ?? [];
        unset($_SESSION[self::OLD_INPUT_KEY]);

        return is_array($old) ? $old : [];
    }
}
