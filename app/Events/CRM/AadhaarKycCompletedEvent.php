<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\AadhaarEkycLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-DM-007 — Fired when Aadhaar eKYC is completed for a lead
final class AadhaarKycCompletedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AadhaarEkycLog $ekycLog
    ) {}
}
