<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class SecretReader
{
    /** @param list<string> $aliases */
    public static function read(string $name, array $aliases = [], bool $required = true): string
    {
        foreach ([$name, ...$aliases] as $candidate) {
            $file = self::environment("{$candidate}_FILE");
            if ($file !== null) {
                return self::readFile($file, $candidate);
            }

            $value = self::environment($candidate);
            if ($value !== null) {
                return $value;
            }
        }

        if ($required) {
            throw new RuntimeException(self::message('missing_secret', ['name' => $name]));
        }

        return '';
    }

    public static function applicationKey(bool $required = true): string
    {
        $key = self::read('APP_KEY', ['FLASK_SECRET_KEY'], $required);
        if ($key === '') {
            return '';
        }
        if (str_starts_with($key, 'base64:')) {
            return $key;
        }

        if (strlen($key) === 32) {
            return $key;
        }

        return 'base64:'.base64_encode(hash('sha256', $key, true));
    }

    private static function readFile(string $path, string $name): string
    {
        $content = @file_get_contents($path);
        if (! is_string($content)) {
            throw new RuntimeException(self::message('invalid_secret_file', ['name' => $name]));
        }

        $content = trim($content);
        if ($content === '') {
            throw new RuntimeException(self::message('invalid_secret_file', ['name' => $name]));
        }

        return $content;
    }

    private static function environment(string $name): ?string
    {
        $value = getenv($name);
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** @param array<string, string> $replace */
    private static function message(string $key, array $replace): string
    {
        $message = trans("site.{$key}");

        $placeholders = [];
        foreach ($replace as $name => $value) {
            $placeholders[":{$name}"] = $value;
        }

        return is_string($message) ? strtr($message, $placeholders) : $key;
    }
}
