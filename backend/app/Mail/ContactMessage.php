<?php

declare(strict_types=1);

namespace App\Mail;

use App\ValueObjects\ContactData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ContactData $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->contact->subject,
            replyTo: [new Address($this->contact->email, $this->contact->name)],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.contact');
    }
}
