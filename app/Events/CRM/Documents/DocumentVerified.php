<?php

declare(strict_types=1);

namespace App\Events\CRM\Documents;

use App\Models\CRM\Documents\ApplicationDocument;
use Illuminate\Foundation\Events\Dispatchable;

// BRD: CRM-DM-004
class DocumentVerified
{
    use Dispatchable;

    public function __construct(public readonly ApplicationDocument $document) {}
}
