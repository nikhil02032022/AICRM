<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Counselling;

use App\Models\CRM\CounsellorAvailabilitySlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

// BRD: CRM-EC-015 — Availability slot repository contract
interface CounsellorAvailabilitySlotRepositoryInterface
{
    public function createSlot(int $institutionId, array $data): CounsellorAvailabilitySlot;

    public function deleteSlot(CounsellorAvailabilitySlot $slot): void;

    /**
     * Return active slots for a counsellor on a specific date (checks recurring day-of-week + one-off).
     *
     * @return Collection<int, CounsellorAvailabilitySlot>
     */
    public function getForCounsellorOnDate(int $counsellorId, Carbon $date): Collection;

    /**
     * Return all active slots for an institution (for public booking).
     *
     * @return Collection<int, CounsellorAvailabilitySlot>
     */
    public function getActiveForInstitution(int $institutionId): Collection;
}
