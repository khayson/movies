<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $digestData
     */
    public function __construct(
        public User $user,
        public array $digestData,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Weekly StreamVault Digest',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-digest',
        );
    }
}
