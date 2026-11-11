<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $recipientName,
        public string $purpose = 'password',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === 'pin'
                ? 'Kode Verifikasi Reset PIN Bank Mini'
                : 'Kode Verifikasi Reset Password Bank Mini',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-otp',
        );
    }
}
