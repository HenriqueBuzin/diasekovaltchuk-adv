<?php

declare(strict_types=1);

namespace App\Services\Dns;

use App\Contracts\MxResolver;
use Closure;

final readonly class NativeMxResolver implements MxResolver
{
    private Closure $lookup;

    public function __construct(Closure $lookup)
    {
        $this->lookup = $lookup;
    }

    public function resolve(string $domain): array
    {
        $records = ($this->lookup)($domain, DNS_MX);
        if (! is_array($records)) {
            return [];
        }

        $exchanges = [];
        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            if (is_string($record['target'] ?? null)) {
                $exchanges[] = $record['target'];
            }
        }

        return $exchanges;
    }
}
