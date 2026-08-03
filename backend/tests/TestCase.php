<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @param array<string, mixed> $values */
    protected function configure(array $values): void
    {
        foreach ($values as $key => $value) {
            config()->set($key, $value);
        }
    }
}
