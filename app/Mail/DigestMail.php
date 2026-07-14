<?php

namespace App\Mail;

use App\Models\DigestRun;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly DigestRun $run) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->run->destination->config['subject'] ?? 'Event digest');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.digest');
    }
}
