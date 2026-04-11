<?php

declare(strict_types=1);

namespace App\Services\CRM\Erp;

use App\DTOs\CRM\ErpStudentDTO;

// BRD: CRM-LC-020 — Contract for ERP Student Master outbound lookup
interface ErpApiClientInterface
{
    /**
     * Look up a student or alumni in the A2A ERP Student Master by mobile number.
     *
     * Returns null when no match is found (HTTP 404) or when the API is
     * temporarily unavailable (handled gracefully — never throws).
     */
    public function lookupStudentByMobile(string $mobile): ?ErpStudentDTO;
}
