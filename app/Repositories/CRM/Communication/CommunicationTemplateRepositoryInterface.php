<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\DTOs\CRM\CreateCommunicationTemplateDTO;
use App\Models\CRM\CommunicationTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CommunicationTemplateRepositoryInterface
{
    public function create(CreateCommunicationTemplateDTO $dto): CommunicationTemplate;

    public function findByUuidOrFail(string $uuid): CommunicationTemplate;

    /** @param array<string, mixed> $data */
    public function update(CommunicationTemplate $template, array $data): CommunicationTemplate;

    public function delete(CommunicationTemplate $template): void;

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator;
}
