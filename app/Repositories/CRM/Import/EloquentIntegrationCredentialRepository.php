<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Import;

use App\Models\CRM\IntegrationCredential;
use Illuminate\Database\Eloquent\Collection;

// BRD: CRM-SA-010 — Eloquent repository for integration credential persistence
final class EloquentIntegrationCredentialRepository implements IntegrationCredentialRepositoryInterface
{
    /** @param array<string, mixed> $data */
    public function create(array $data, int $institutionId): IntegrationCredential
    {
        return IntegrationCredential::create(array_merge($data, [
            'institution_id' => $institutionId,
        ]));
    }

    public function findByUuid(string $uuid): ?IntegrationCredential
    {
        return IntegrationCredential::where('uuid', $uuid)->first();
    }

    public function findByUuidOrFail(string $uuid): IntegrationCredential
    {
        return IntegrationCredential::where('uuid', $uuid)->firstOrFail();
    }

    /**
     * Bypass InstitutionScope — webhook middleware has no auth context.
     * Still filters by is_active to prevent use of deactivated credentials.
     */
    public function findActiveByUuidWithoutScope(string $uuid): ?IntegrationCredential
    {
        return IntegrationCredential::withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();
    }

    /** @return Collection<int, IntegrationCredential> */
    public function allForInstitution(int $institutionId): Collection
    {
        return IntegrationCredential::where('institution_id', $institutionId)
            ->orderBy('channel')
            ->orderBy('label')
            ->get();
    }

    /** @param array<string, mixed> $data */
    public function update(IntegrationCredential $credential, array $data): IntegrationCredential
    {
        $credential->update($data);

        return $credential->refresh();
    }

    public function softDelete(IntegrationCredential $credential): void
    {
        $credential->delete();
    }
}
