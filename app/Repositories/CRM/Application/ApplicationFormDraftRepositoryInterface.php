<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Models\CRM\ApplicationFormDraft;

interface ApplicationFormDraftRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data): ApplicationFormDraft;

    /** @param array<string, mixed> $data */
    public function update(ApplicationFormDraft $draft, array $data): ApplicationFormDraft;

    public function findByResumeTokenOrFail(string $resumeToken, int $institutionId): ApplicationFormDraft;

    public function resumeTokenExists(string $resumeToken): bool;
}
