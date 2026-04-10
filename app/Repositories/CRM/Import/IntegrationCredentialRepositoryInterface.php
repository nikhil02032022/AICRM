<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Import;

use App\Models\CRM\IntegrationCredential;
use Illuminate\Database\Eloquent\Collection;

interface IntegrationCredentialRepositoryInterface
{
    public function create(array $data, int $institutionId): IntegrationCredential;

    public function findByUuid(string $uuid): ?IntegrationCredential;

    public function findByUuidOrFail(string $uuid): IntegrationCredential;

    /**
     * Find an active credential by UUID — used in webhook middleware to verify signature.
     * Uses withoutGlobalScopes so the webhook can resolve by UUID without auth context.
     */
    public function findActiveByUuidWithoutScope(string $uuid): ?IntegrationCredential;

    /** @return Collection<int, IntegrationCredential> */
    public function allForInstitution(int $institutionId): Collection;

    /** @param array<string, mixed> $data */
    public function update(IntegrationCredential $credential, array $data): IntegrationCredential;

    public function softDelete(IntegrationCredential $credential): void;
}
