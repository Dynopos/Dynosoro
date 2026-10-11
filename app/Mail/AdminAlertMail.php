<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AdminAlertMail extends Mailable
{
    use Queueable;

    public function __construct(public string $alertSubject, public string $body) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Dyno SEO] '.$this->alertSubject);
    }

    public function content(): Content
    {
        return new Content(text: 'mail.admin-alert');
    }
}
