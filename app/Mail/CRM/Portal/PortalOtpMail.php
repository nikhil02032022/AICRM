<?php

declare(strict_types=1);

namespace App\Mail\CRM\Portal;

use App\Models\CRM\Institution;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-SP-002 — Delivers 6-digit OTP to the applicant's registered email address
final class PortalOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otp,
        public readonly Institution $institution,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your login code for ' . $this->institution->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.portal.otp',
            with: [
                'otp'             => $this->otp,
                'institutionName' => $this->institution->name,
                'expiryMinutes'   => config('crm_portal.otp_expiry_minutes', 10),
            ],
        );
    }
}
