<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Activity;

use App\DTOs\CRM\CreateActivityDTO;
use App\Enums\CRM\ActivityType;
use App\Models\CRM\Activity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-EC-004 — Repository interface for CRM activity timeline entries
interface ActivityRepositoryInterface
{
    /**
     * Create an activity entry linked to any subject model.
     */
    public function createForSubject(CreateActivityDTO $dto): Activity;

    /**
     * Paginate activities for a subject (e.g. a lead), newest first.
     *
     * @param  class-string  $subjectType
     */
    public function paginateForSubject(
        string $subjectType,
        int $subjectId,
        int $institutionId,
        int $perPage = 20,
    ): LengthAwarePaginator;

    /**
     * Create a system-generated activity (no performed_by).
     *
     * @param  array<string, mixed>|null  $metadata  Must NOT contain PII.
     */
    public function createSystemEntry(
        string $subjectType,
        int $subjectId,
        int $institutionId,
        ActivityType $type,
        string $body,
        ?array $metadata = null,
    ): Activity;
}
