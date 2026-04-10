<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Communication;

use App\DTOs\CRM\CreateCommunicationTemplateDTO;
use App\Models\CRM\CommunicationTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EloquentCommunicationTemplateRepository implements CommunicationTemplateRepositoryInterface
{
    public function create(CreateCommunicationTemplateDTO $dto): CommunicationTemplate
    {
        return CommunicationTemplate::create([
            'institution_id' => $dto->institutionId,
            'campus_id'      => $dto->campusId,
            'name'           => $dto->name,
            'channel'        => $dto->channel,
            'type'           => $dto->type,
            'subject'        => $dto->subject,
            'body_html'      => $dto->bodyHtml,
            'body_text'      => $dto->bodyText,
            'merge_tags'     => $dto->mergeTags,
            'created_by'     => $dto->createdBy,
        ]);
    }

    public function findByUuidOrFail(string $uuid): CommunicationTemplate
    {
        return CommunicationTemplate::where('uuid', $uuid)->firstOrFail();
    }

    /** @param array<string, mixed> $data */
    public function update(CommunicationTemplate $template, array $data): CommunicationTemplate
    {
        $template->update($data);

        return $template->fresh();
    }

    public function delete(CommunicationTemplate $template): void
    {
        $template->delete();
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = CommunicationTemplate::query();

        if (! empty($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }
}
