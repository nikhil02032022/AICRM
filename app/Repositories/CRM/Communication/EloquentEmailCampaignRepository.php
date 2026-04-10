<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\DTOs\CRM\CreateEmailCampaignDTO;
use App\Enums\CRM\CampaignStatus;
use App\Models\CRM\EmailCampaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentEmailCampaignRepository implements EmailCampaignRepositoryInterface
{
    public function create(CreateEmailCampaignDTO $dto): EmailCampaign
    {
        return EmailCampaign::create([
            'institution_id'   => $dto->institutionId,
            'campus_id'        => $dto->campusId,
            'name'             => $dto->name,
            'subject'          => $dto->subject,
            'template_id'      => $dto->templateId,
            'from_name'        => $dto->fromName,
            'from_email'       => $dto->fromEmail,
            'recipient_filter' => $dto->recipientFilter,
            'scheduled_at'     => $dto->scheduledAt,
            'created_by'       => $dto->createdBy,
            'status'           => $dto->scheduledAt !== null ? CampaignStatus::SCHEDULED : CampaignStatus::DRAFT,
        ]);
    }

    public function findByUuidOrFail(string $uuid): EmailCampaign
    {
        return EmailCampaign::where('uuid', $uuid)->firstOrFail();
    }

    /** @param array<string, mixed> $data */
    public function update(EmailCampaign $campaign, array $data): EmailCampaign
    {
        $campaign->update($data);

        return $campaign->fresh();
    }

    public function delete(EmailCampaign $campaign): void
    {
        $campaign->delete();
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = EmailCampaign::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
