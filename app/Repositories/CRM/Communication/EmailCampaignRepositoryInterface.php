<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\DTOs\CRM\CreateEmailCampaignDTO;
use App\Models\CRM\EmailCampaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EmailCampaignRepositoryInterface
{
    public function create(CreateEmailCampaignDTO $dto): EmailCampaign;

    public function findByUuidOrFail(string $uuid): EmailCampaign;

    /** @param array<string, mixed> $data */
    public function update(EmailCampaign $campaign, array $data): EmailCampaign;

    public function delete(EmailCampaign $campaign): void;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator;
}
