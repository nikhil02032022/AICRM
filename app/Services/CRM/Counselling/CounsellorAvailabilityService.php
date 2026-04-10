<?php

declare(strict_types=1);

namespace App\Services\CRM\Counselling;

use App\Models\CRM\CounsellingSession;
use App\Models\CRM\CounsellorAvailabilitySlot;
use App\Repositories\CRM\Counselling\CounsellorAvailabilitySlotRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

// BRD: CRM-EC-015 — Counsellor availability management
// BRD: CRM-EC-016 — Computes available time slots for public booking calendar
final class CounsellorAvailabilityService
{
    public function __construct(
        private readonly CounsellorAvailabilitySlotRepositoryInterface $slotRepository,
    ) {}

    /** @param array<string, mixed> $data */
    public function addSlot(int $institutionId, array $data): CounsellorAvailabilitySlot
    {
        return $this->slotRepository->createSlot($institutionId, $data);
    }

    public function removeSlot(CounsellorAvailabilitySlot $slot): void
    {
        $this->slotRepository->deleteSlot($slot);
    }

    /**
     * Compute available time slots for a counsellor on a date, minus already-booked sessions.
     *
     * @return SupportCollection<int, array{time: string, display: string}>
     */
    public function getAvailableTimesForDate(int $counsellorId, Carbon $date): SupportCollection
    {
        $slots = $this->slotRepository->getForCounsellorOnDate($counsellorId, $date);

        if ($slots->isEmpty()) {
            return collect();
        }

        $booked = CounsellingSession::withoutGlobalScopes()
            ->where('counsellor_id', $counsellorId)
            ->whereDate('scheduled_at', $date->toDateString())
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->pluck('scheduled_at')
            ->map(fn ($dt) => Carbon::parse($dt)->format('H:i'))
            ->all();

        $times = collect();

        foreach ($slots as $slot) {
            $cursor = Carbon::createFromTimeString($slot->start_time);
            $end = Carbon::createFromTimeString($slot->end_time);

            while ($cursor->lessThan($end)) {
                $timeStr = $cursor->format('H:i');

                if (!in_array($timeStr, $booked, true)) {
                    $times->push([
                        'time' => $timeStr,
                        'display' => $cursor->format('g:i A'),
                    ]);
                }
                $cursor->addMinutes($slot->slot_duration_minutes);
            }
        }

        return $times;
    }

    /**
     * @return Collection<int, CounsellorAvailabilitySlot>
     */
    public function getActiveForInstitution(int $institutionId): Collection
    {
        return $this->slotRepository->getActiveForInstitution($institutionId);
    }
}
