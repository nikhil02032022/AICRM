<?php

declare(strict_types=1);

namespace App\Policies\CRM;

use App\Models\CRM\OfferLetter;
use App\Models\User;

// BRD: CRM-AP-012 — Authorization policies for offer letter operations
final class OfferLetterPolicy
{
    /**
     * Can the user view this offer letter?
     */
    public function view(User $user, OfferLetter $offerLetter): bool
    {
        // Users can view offers for their own applications or if they're an admissions staff member
        return $user->hasRole(['admin', 'institution-admin', 'counsellor', 'admissions-staff'])
            || $user->institution_id === $offerLetter->institution_id;
    }

    /**
     * Can the user update (accept/decline) this offer letter?
     */
    public function update(User $user, OfferLetter $offerLetter): bool
    {
        // Counsellors and admissions staff can manage offers
        return $user->hasRole(['admin', 'institution-admin', 'counsellor', 'admissions-staff'])
            && $user->institution_id === $offerLetter->institution_id;
    }

    /**
     * Can the user send this offer letter?
     */
    public function send(User $user, OfferLetter $offerLetter): bool
    {
        return $user->hasPermissionTo('crm.communication.send')
            && $user->institution_id === $offerLetter->institution_id;
    }
}
