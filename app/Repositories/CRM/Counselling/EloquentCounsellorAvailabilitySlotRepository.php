<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Counselling;

use App\Models\CRM\CounsellorAvailabilitySlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

// BRD: CRM-EC-015 — Eloquent implementation for counsellor availability slots
final class EloquentCounsellorAvailabilitySlotRepository implements CounsellorAvailabilitySlotRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function createSlot(int $institutionId, array $data): CounsellorAvailabilitySlot
    {
        return CounsellorAvailabilitySlot::withoutGlobalScopes()->create(
            array_merge($data, ['institution_id' => $institutionId])
        );
    }

    public function deleteSlot(CounsellorAvailabilitySlot $slot): void
    {
        $slot->delete();
    }

    public function getForCounsellorOnDate(int $counsellorId, Carbon $date): Collection
    {
        $dayOfWeek = (int) $date->dayOfWeek;

        return CounsellorAvailabilitySlot::withoutGlobalScopes()
            ->where('counsellor_id', $counsellorId)
            ->where('is_active', true)
            ->where(function ($q) use ($dayOfWeek, $date): void {
                $q->where('day_of_week', $dayOfWeek)
                    ->orWhere('slot_date', $date->toDateString());
            })
            ->orderBy('start_time')
            ->get();
    }

    public function getActiveForInstitution(int $institutionId): Collection
    {
        return CounsellorAvailabilitySlot::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->with('counsellor:id,name')
            ->orderBy('counsellor_id')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }
}
