<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Counselling;

use App\DTOs\CRM\BookSessionDTO;
use App\DTOs\CRM\UpdateSessionDTO;
use App\Enums\CRM\CounsellingSessionStatus;
use App\Models\CRM\CounsellingSession;
use App\Models\CRM\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

// BRD: CRM-EC-015 — Eloquent implementation for counselling sessions
final class EloquentCounsellingSessionRepository implements CounsellingSessionRepositoryInterface
{
    public function create(BookSessionDTO $dto): CounsellingSession
    {
        return CounsellingSession::withoutGlobalScopes()->create([
            'institution_id' => $this->resolveInstitutionId($dto->leadId),
            'lead_id' => $dto->leadId,
            'counsellor_id' => $dto->counsellorId,
            'availability_slot_id' => $dto->availabilitySlotId,
            'session_type' => $dto->sessionType,
            'status' => CounsellingSessionStatus::SCHEDULED,
            'mode' => $dto->mode,
            'scheduled_at' => $dto->scheduledAt,
            'pre_session_notes' => $dto->preSessionNotes,
        ]);
    }

    public function findByUuid(string $uuid): ?CounsellingSession
    {
        return CounsellingSession::withoutGlobalScopes()->where('id', $uuid)->first();
    }

    public function findByBookingToken(string $token): ?CounsellingSession
    {
        return CounsellingSession::withoutGlobalScopes()
            ->where('booking_token', $token)
            ->where('booking_token_expires_at', '>', now())
            ->first();
    }

    public function update(CounsellingSession $session, UpdateSessionDTO $dto): CounsellingSession
    {
        $session->update([
            'status' => $dto->status,
            'post_session_notes' => $dto->postSessionNotes,
            'completed_at' => $dto->status === CounsellingSessionStatus::COMPLETED ? now() : null,
        ]);

        return $session->refresh();
    }

    public function paginateForLead(string $leadUuid, int $perPage = 10): LengthAwarePaginator
    {
        return CounsellingSession::withoutGlobalScopes()
            ->whereHas('lead', fn ($q) => $q->where('uuid', $leadUuid))
            ->with(['counsellor:id,name'])
            ->orderByDesc('scheduled_at')
            ->paginate($perPage);
    }

    public function pendingReminders24h(): Collection
    {
        $window = now()->addHours(24);

        return CounsellingSession::withoutGlobalScopes()
            ->where('reminder_24h_sent', false)
            ->whereIn('status', [CounsellingSessionStatus::SCHEDULED->value, CounsellingSessionStatus::CONFIRMED->value])
            ->whereBetween('scheduled_at', [now(), $window])
            ->with(['lead', 'counsellor'])
            ->get();
    }

    public function pendingReminders1h(): Collection
    {
        $window = now()->addHour();

        return CounsellingSession::withoutGlobalScopes()
            ->where('reminder_1h_sent', false)
            ->whereIn('status', [CounsellingSessionStatus::SCHEDULED->value, CounsellingSessionStatus::CONFIRMED->value])
            ->whereBetween('scheduled_at', [now(), $window])
            ->with(['lead', 'counsellor'])
            ->get();
    }

    // -------------------------------------------------------------------------

    private function resolveInstitutionId(int $leadId): int
    {
        return Lead::withoutGlobalScopes()
            ->where('id', $leadId)
            ->value('institution_id') ?? 0;
    }
}
