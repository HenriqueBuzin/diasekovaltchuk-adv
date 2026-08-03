<?php

declare(strict_types=1);

namespace App\Contracts;

interface MxResolver
{
    /** @return list<string> */
    public function resolve(string $domain): array;
}
