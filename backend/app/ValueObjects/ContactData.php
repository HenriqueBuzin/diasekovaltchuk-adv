<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class ContactData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $subject,
        public string $message,
    ) {}

}
