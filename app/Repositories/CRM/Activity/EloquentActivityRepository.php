<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Activity;

use App\DTOs\CRM\CreateActivityDTO;
use App\Enums\CRM\ActivityType;
use App\Models\CRM\Activity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-EC-004 — Eloquent implementation of the activity timeline repository
final class EloquentActivityRepository implements ActivityRepositoryInterface
{
    public function createForSubject(CreateActivityDTO $dto): Activity
    {
        return Activity::withoutGlobalScopes()->create([
            'institution_id' => $dto->institutionId,
            'subject_type' => $dto->subjectType,
            'subject_id' => $dto->subjectId,
            'type' => $dto->type->value,
            'direction' => $dto->direction,
            'channel' => $dto->channel,
            'body' => $dto->body,
            'performed_by_id' => $dto->performedById,
            'metadata' => $dto->metadata,
        ]);
    }

    public function paginateForSubject(
        string $subjectType,
        int $subjectId,
        int $institutionId,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return Activity::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->with('performedBy:id,name')
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function createSystemEntry(
        string $subjectType,
        int $subjectId,
        int $institutionId,
        ActivityType $type,
        string $body,
        ?array $metadata = null,
    ): Activity {
        return $this->createForSubject(new CreateActivityDTO(
            type: $type,
            subjectType: $subjectType,
            subjectId: $subjectId,
            institutionId: $institutionId,
            body: $body,
            channel: null,
            direction: 'internal',
            metadata: $metadata,
            performedById: null,
        ));
    }
}
