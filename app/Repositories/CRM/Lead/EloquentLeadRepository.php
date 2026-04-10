<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Lead;

use App\DTOs\CRM\CreateLeadDTO;
use App\Enums\CRM\LeadStatus;
use App\Enums\CRM\LeadTemperature;
use App\Models\CRM\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class EloquentLeadRepository implements LeadRepositoryInterface
{
    public function create(CreateLeadDTO $dto, int $institutionId): Lead
    {
        return Lead::create([
            'institution_id' => $institutionId,
            'campus_id' => $dto->campusId,
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'mobile' => $dto->mobile,
            'email' => $dto->email,
            'source' => $dto->source,
            'source_utm_params' => $dto->sourceUtmParams,
            'lead_score' => 0,
            'temperature' => LeadTemperature::COLD->value,
            'status' => LeadStatus::NEW_ENQUIRY->value,
            'consent_given' => $dto->consentGiven,
            'consent_timestamp' => $dto->consentGiven ? now() : null,
            'consent_ip' => $dto->consentIp,
            'consent_form_version' => $dto->consentFormVersion,
            'city' => $dto->city,
            'state' => $dto->state,
            'notes' => $dto->notes,
        ]);
    }

    public function findByUuid(string $uuid): ?Lead
    {
        return Lead::where('uuid', $uuid)->first();
    }

    public function findByUuidOrFail(string $uuid): Lead
    {
        return Lead::where('uuid', $uuid)->firstOrFail();
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Lead::select([
            'id', 'uuid', 'first_name', 'last_name',
            'lead_score', 'temperature', 'status', 'source',
            'assigned_counsellor_id', 'created_at',
        ])->with(['assignedCounsellor:id,name']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['temperature'])) {
            $query->where('temperature', $filters['temperature']);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (isset($filters['assigned_counsellor_id'])) {
            $query->where('assigned_counsellor_id', $filters['assigned_counsellor_id']);
        }

        if (!empty($filters['search'])) {
            // BRD: CRM-CR-002 — Do not log PII; search by name only (not mobile/email) in list queries
            $term = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($term): void {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term);
            });
        }

        $sortField = in_array($filters['sort'] ?? '', ['created_at', 'lead_score', 'status'], true)
            ? $filters['sort']
            : 'created_at';

        $sortDirection = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    /** @param array<string, mixed> $data */
    public function update(Lead $lead, array $data): Lead
    {
        $lead->update($data);

        return $lead->refresh();
    }

    public function softDelete(Lead $lead): void
    {
        $lead->delete();
    }

    /**
     * BRD: CRM-LC-018 — Find duplicate leads by mobile or email within the same institution.
     * Uses withoutGlobalScopes to bypass InstitutionScope so we can scope manually here.
     *
     * @return Collection<int, Lead>
     */
    public function findDuplicates(string $mobile, ?string $email, int $institutionId): Collection
    {
        // Because mobile/email are encrypted, we must use model-level comparison.
        // Pull only candidates for the institution and compare in PHP.
        // For large datasets, this should be replaced with a deterministic hash column.
        return Lead::withoutGlobalScopes()
            ->where('institution_id', $institutionId)
            ->whereNull('deleted_at')
            ->get(['id', 'uuid', 'mobile', 'email', 'first_name', 'last_name', 'status'])
            ->filter(fn (Lead $l) => $l->mobile === $mobile || ($email && $l->email === $email))
            ->values();
    }

    /** @param list<int> $programmeIds */
    public function syncProgrammeInterests(Lead $lead, array $programmeIds): void
    {
        if (empty($programmeIds)) {
            return;
        }

        $syncData = [];

        foreach ($programmeIds as $index => $programmeId) {
            $syncData[$programmeId] = ['is_primary' => $index === 0];
        }

        $lead->programmeInterests()->sync($syncData);
    }
}
