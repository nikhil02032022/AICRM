<?php

declare(strict_types=1);

namespace App\Services\CRM\Counselling;

use App\DTOs\CRM\BookSessionDTO;
use App\DTOs\CRM\UpdateSessionDTO;
use App\Enums\CRM\CounsellingSessionStatus;
use App\Events\CRM\CounsellingSessionBookedEvent;
use App\Events\CRM\CounsellingSessionCancelledEvent;
use App\Events\CRM\CounsellingSessionCompletedEvent;
use App\Models\CRM\CounsellingSession;
use App\Repositories\CRM\Counselling\CounsellingSessionRepositoryInterface;
use Illuminate\Support\Str;

// BRD: CRM-EC-015 — Session booking, outcome recording, and cancellation
final class CounsellingService
{
    public function __construct(
        private readonly CounsellingSessionRepositoryInterface $sessionRepository,
    ) {}

    // BRD: CRM-EC-015, CRM-EC-016 — Book a new counselling session
    public function book(BookSessionDTO $dto): CounsellingSession
    {
        $session = $this->sessionRepository->create($dto);

        event(new CounsellingSessionBookedEvent($session));

        return $session;
    }

    // BRD: CRM-EC-015 — Record session outcome (completed / cancelled / no_show)
    public function updateOutcome(CounsellingSession $session, UpdateSessionDTO $dto): CounsellingSession
    {
        if ($session->status->isTerminal()) {
            throw new \DomainException("Cannot update a {$session->status->label()} session.");
        }

        $updated = $this->sessionRepository->update($session, $dto);

        match ($dto->status) {
            CounsellingSessionStatus::COMPLETED => event(new CounsellingSessionCompletedEvent($updated)),
            CounsellingSessionStatus::CANCELLED => event(new CounsellingSessionCancelledEvent($updated)),
            CounsellingSessionStatus::NO_SHOW => event(new CounsellingSessionCancelledEvent($updated)),
            default => null,
        };

        return $updated;
    }

    // BRD: CRM-EC-016 — Generate a short-lived public booking token (2 hours)
    public function generateBookingToken(CounsellingSession $session): string
    {
        $token = Str::random(48);
        $session->update([
            'booking_token' => $token,
            'booking_token_expires_at' => now()->addHours(2),
        ]);

        return $token;
    }
}
