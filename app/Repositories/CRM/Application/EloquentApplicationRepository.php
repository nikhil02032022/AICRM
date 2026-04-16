<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Models\CRM\Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentApplicationRepository implements ApplicationRepositoryInterface
{
    public function create(array $data): Application
    {
        return Application::create($data);
    }

    public function findByUuidOrFail(string $uuid): Application
    {
        return Application::whereUuid($uuid)->firstOrFail();
    }

    public function findByLeadUuidOrFail(string $leadUuid): ?Application
    {
        return Application::whereLeadUuid($leadUuid)->first();
    }

    public function update(Application $application, array $data): Application
    {
        $application->update($data);

        return $application->refresh();
    }

    public function softDelete(Application $application): void
    {
        $application->delete();
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Application::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assigned_counsellor_id'])) {
            $query->where('assigned_counsellor_id', $filters['assigned_counsellor_id']);
        }

        if (isset($filters['admission_cycle_uuid'])) {
            $query->where('admission_cycle_uuid', $filters['admission_cycle_uuid']);
        }

        if (isset($filters['programme_id'])) {
            $query->whereHas('lead.programmeInterests', function ($q) use ($filters): void {
                $q->whereKey((int) $filters['programme_id']);
            });
        }

        if (isset($filters['batch'])) {
            $batch = (string) $filters['batch'];
            $query->whereHas('lead', function ($q) use ($batch): void {
                $q->where('preferred_intake', $batch)
                    ->orWhereHas('programmeInterests', function ($programmeQuery) use ($batch): void {
                        $programmeQuery->where('lead_programme_interests.preferred_intake', $batch);
                    });
            });
        }

        if (isset($filters['source'])) {
            $query->whereHas('lead', function ($q) use ($filters): void {
                $q->where('source', $filters['source']);
            });
        }

        if (isset($filters['score_min'])) {
            $query->whereHas('lead', function ($q) use ($filters): void {
                $q->where('lead_score', '>=', (int) $filters['score_min']);
            });
        }

        if (isset($filters['score_max'])) {
            $query->whereHas('lead', function ($q) use ($filters): void {
                $q->where('lead_score', '<=', (int) $filters['score_max']);
            });
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('submitted_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('submitted_at', '<=', $filters['to_date']);
        }

        if (isset($filters['search'])) {
            // Search by lead name or email (assumes lead relationship is available)
            $searchTerm = "%{$filters['search']}%";
            $query->whereHas('lead', function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm);
            });
        }

        return $query->with(['lead', 'assignedCounsellor', 'currentOfferLetter'])
            ->orderByDesc('submitted_at')
            ->paginate($perPage);
    }

    public function all(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Application::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['assigned_counsellor_id'])) {
            $query->where('assigned_counsellor_id', $filters['assigned_counsellor_id']);
        }

        if (isset($filters['source'])) {
            $query->whereHas('lead', function ($q) use ($filters): void {
                $q->where('source', $filters['source']);
            });
        }

        return $query->with(['lead:id,uuid,first_name,email,mobile'])
            ->get();
    }

    public function findManyByUuids(array $uuids): Collection
    {
        return Application::query()
            ->whereIn('uuid', $uuids)
            ->with(['lead', 'assignedCounsellor'])
            ->get();
    }

    public function bulkAssignCounsellorByUuids(array $uuids, int $counsellorId): int
    {
        return Application::query()
            ->whereIn('uuid', $uuids)
            ->update([
                'assigned_counsellor_id' => $counsellorId,
                'updated_at' => now(),
            ]);
    }
}
