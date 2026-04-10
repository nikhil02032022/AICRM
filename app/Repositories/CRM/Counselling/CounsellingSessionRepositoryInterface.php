<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Counselling;

use App\DTOs\CRM\BookSessionDTO;
use App\DTOs\CRM\UpdateSessionDTO;
use App\Models\CRM\CounsellingSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

// BRD: CRM-EC-015 — Sessions repository contract
interface CounsellingSessionRepositoryInterface
{
    public function create(BookSessionDTO $dto): CounsellingSession;

    public function findByUuid(string $uuid): ?CounsellingSession;

    public function findByBookingToken(string $token): ?CounsellingSession;

    public function update(CounsellingSession $session, UpdateSessionDTO $dto): CounsellingSession;

    /** @return LengthAwarePaginator<CounsellingSession> */
    public function paginateForLead(string $leadUuid, int $perPage = 10): LengthAwarePaginator;

    /** @return Collection<int, CounsellingSession> */
    public function pendingReminders24h(): Collection;

    /** @return Collection<int, CounsellingSession> */
    public function pendingReminders1h(): Collection;
}
