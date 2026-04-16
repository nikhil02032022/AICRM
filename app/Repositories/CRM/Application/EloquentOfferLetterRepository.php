<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\Models\CRM\OfferLetter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentOfferLetterRepository implements OfferLetterRepositoryInterface
{
    public function create(array $data): OfferLetter
    {
        return OfferLetter::create($data);
    }

    public function findByUuidOrFail(string $uuid): OfferLetter
    {
        return OfferLetter::whereUuid($uuid)->firstOrFail();
    }

    public function findByApplicationUuid(string $applicationUuid): ?OfferLetter
    {
        return OfferLetter::whereApplicationUuid($applicationUuid)
            ->whereIn('status', ['pending', 'generated', 'sent', 'accepted'])
            ->orderByDesc('generated_at')
            ->first();
    }

    public function update(OfferLetter $offerLetter, array $data): OfferLetter
    {
        $offerLetter->update($data);

        return $offerLetter->refresh();
    }

    public function softDelete(OfferLetter $offerLetter): void
    {
        $offerLetter->delete();
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = OfferLetter::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['programme_uuid'])) {
            $query->where('programme_uuid', $filters['programme_uuid']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('generated_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('generated_at', '<=', $filters['to_date']);
        }

        if (isset($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $query->whereHas('lead', function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm);
            });
        }

        return $query->with(['application', 'lead'])
            ->orderByDesc('generated_at')
            ->paginate($perPage);
    }
}
