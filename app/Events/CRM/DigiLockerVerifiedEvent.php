<?php

declare(strict_types=1);

namespace App\Events\CRM;

use App\Models\CRM\DigiLockerDocument;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-DM-006 — Fired when a DigiLocker document is successfully verified
final class DigiLockerVerifiedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DigiLockerDocument $document
    ) {}
}
