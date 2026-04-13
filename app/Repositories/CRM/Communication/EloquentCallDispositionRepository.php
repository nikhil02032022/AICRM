<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\Models\CRM\CallDispositionConfig;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class EloquentCallDispositionRepository implements CallDispositionRepositoryInterface
{
    public function paginate(int $institutionId, int $perPage = 20): LengthAwarePaginator
    {
        return CallDispositionConfig::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->paginate($perPage);
    }

    public function activeForInstitution(int $institutionId): Collection
    {
        return CallDispositionConfig::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    public function findByCode(int $institutionId, string $code): ?CallDispositionConfig
    {
        return CallDispositionConfig::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->where('code', $code)
            ->first();
    }

    public function create(int $institutionId, int $createdBy, array $payload): CallDispositionConfig
    {
        return CallDispositionConfig::withoutGlobalScopes()->create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $institutionId,
            'campus_id' => $payload['campus_id'] ?? null,
            'code' => $payload['code'],
            'label' => $payload['label'],
            'is_active' => (bool) ($payload['is_active'] ?? true),
            'requires_follow_up' => (bool) ($payload['requires_follow_up'] ?? false),
            'sort_order' => (int) ($payload['sort_order'] ?? 1),
            'is_system' => (bool) ($payload['is_system'] ?? false),
        ]);
    }

    public function update(CallDispositionConfig $config, array $payload): CallDispositionConfig
    {
        $config->update([
            'label' => $payload['label'],
            'is_active' => (bool) ($payload['is_active'] ?? false),
            'requires_follow_up' => (bool) ($payload['requires_follow_up'] ?? false),
            'sort_order' => (int) ($payload['sort_order'] ?? 1),
        ]);

        return $config->fresh();
    }
}
