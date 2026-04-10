<?php

declare(strict_types=1);

namespace App\Services\CRM\Communication;

use App\DTOs\CRM\CreateCommunicationTemplateDTO;
use App\Enums\CRM\CommunicationChannel;
use App\Models\CRM\CommunicationTemplate;
use App\Repositories\CRM\Communication\CommunicationTemplateRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

// BRD: CRM-CC-001 — Template CRUD and merge-tag rendering
final class TemplateService
{
    public function __construct(
        private readonly CommunicationTemplateRepositoryInterface $templateRepository,
    ) {}

    public function create(CreateCommunicationTemplateDTO $dto): CommunicationTemplate
    {
        return $this->templateRepository->create($dto);
    }

    /** @param array<string, mixed> $data */
    public function update(CommunicationTemplate $template, array $data): CommunicationTemplate
    {
        return $this->templateRepository->update($template, $data);
    }

    public function delete(CommunicationTemplate $template): void
    {
        $this->templateRepository->delete($template);
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        return $this->templateRepository->paginate($filters, $perPage);
    }

    /**
     * BRD: CRM-CC-001 — Replace {{tag}} merge tokens with actual lead/context data.
     *
     * @param array<string, mixed> $mergeData
     */
    public function render(CommunicationTemplate $template, array $mergeData): string
    {
        $body = $template->channel === CommunicationChannel::EMAIL
            ? ($template->body_html ?? $template->body_text)
            : $template->body_text;

        foreach ($mergeData as $key => $value) {
            $body = Str::replace('{{'.$key.'}}', (string) $value, $body);
        }

        return $body;
    }

    /**
     * BRD: CRM-CC-005 — Ensure marketing emails contain unsubscribe link.
     */
    public function validateMarketingTemplate(CommunicationTemplate $template): bool
    {
        if ($template->type->requiresUnsubscribeLink()) {
            return Str::contains($template->body_html ?? $template->body_text, '{{unsubscribe_link}}');
        }

        return true;
    }
}
