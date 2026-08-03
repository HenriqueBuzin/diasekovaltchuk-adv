<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\ContactMessage;
use App\ValueObjects\ContactData;
use Illuminate\Support\Facades\Mail;

final readonly class ContactMailer
{
    public function __construct(private SiteConfiguration $configuration) {}

    public function send(ContactData $contact): void
    {
        Mail::to($this->configuration->recipients())->send(new ContactMessage($contact));
    }
}
