<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Models\CRM\ApplicationFormDraft;

final class EloquentApplicationFormDraftRepository implements ApplicationFormDraftRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): ApplicationFormDraft
    {
        return ApplicationFormDraft::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(ApplicationFormDraft $draft, array $data): ApplicationFormDraft
    {
        $draft->update($data);

        return $draft->refresh();
    }

    public function findByResumeTokenOrFail(string $resumeToken, int $institutionId): ApplicationFormDraft
    {
        return ApplicationFormDraft::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('resume_token', $resumeToken)
            ->firstOrFail();
    }

    public function resumeTokenExists(string $resumeToken): bool
    {
        return ApplicationFormDraft::withoutGlobalScopes()
            ->where('resume_token', $resumeToken)
            ->whereNull('deleted_at')
            ->exists();
    }
}
