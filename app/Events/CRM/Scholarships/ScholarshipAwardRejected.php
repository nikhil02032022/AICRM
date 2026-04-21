<?php

declare(strict_types=1);

namespace App\Events\CRM\Scholarships;

use App\Models\CRM\Scholarships\ScholarshipAward;
use Illuminate\Foundation\Events\Dispatchable;

// BRD: CRM-FM-008
class ScholarshipAwardRejected
{
    use Dispatchable;

    public function __construct(public readonly ScholarshipAward $award) {}
}
