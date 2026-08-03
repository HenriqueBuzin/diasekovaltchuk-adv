<?php

declare(strict_types=1);

namespace App\Services\Dns;

use InvalidArgumentException;

final readonly class EmailDomainValidator
{
    public function __construct(
        private NativeMxResolver $native,
        private DohMxResolver $fallback,
    ) {}

    public function accepts(string $email): bool
    {
        $domain = $this->domain($email);
        $records = $this->native->resolve($domain);
        if ($records === []) {
            $records = $this->fallback->resolve($domain);
        }

        foreach ($records as $record) {
            $host = trim($record);
            $separator = strpos($host, ' ');
            if ($separator !== false) {
                $priority = substr($host, 0, $separator);
                if (ctype_digit($priority)) {
                    $host = substr($host, $separator + 1);
                }
            }
            $host = rtrim($host, '.');
            if ($host !== '') {
                return true;
            }
        }

        return false;
    }

    public function domain(string $email): string
    {
        $email = trim($email);
        $position = strrpos($email, '@');
        if ($position === false) {
            throw new InvalidArgumentException(trans('site.valid_email'));
        }
        if ($position === 0 || $position === strlen($email) - 1) {
            throw new InvalidArgumentException(trans('site.valid_email'));
        }

        $domain = substr($email, $position + 1);

        return strtolower($domain);
    }
}
