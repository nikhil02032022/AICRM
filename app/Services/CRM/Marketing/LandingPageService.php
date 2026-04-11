<?php

declare(strict_types=1);

namespace App\Services\CRM\Marketing;

use App\DTOs\CRM\CreateLandingPageDTO;
use App\Events\CRM\LandingPageCreatedEvent;
use App\Models\CRM\LandingPage;
use App\Models\CRM\LandingPageView;
use App\Repositories\CRM\Marketing\LandingPageRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

// BRD: CRM-LC-005 — Service for creating and publishing attribution-aware landing pages
final class LandingPageService
{
    public function __construct(
        private readonly LandingPageRepositoryInterface $repository,
    ) {}

    public function create(CreateLandingPageDTO $dto, int $institutionId, int $userId): LandingPage
    {
        $slug = $dto->slug !== ''
            ? $dto->slug
            : $this->repository->generateUniqueSlug($dto->name, $institutionId);

        $landingPage = $this->repository->create(
            new CreateLandingPageDTO(
                name: $dto->name,
                slug: $slug,
                status: $dto->status,
                themeVariant: $dto->themeVariant,
                headline: $dto->headline,
                subheadline: $dto->subheadline,
                heroImageUrl: $dto->heroImageUrl,
                ctaLabel: $dto->ctaLabel,
                ctaSecondaryLabel: $dto->ctaSecondaryLabel,
                content: $dto->content,
                attributionParams: $dto->attributionParams,
                seoTitle: $dto->seoTitle,
                seoDescription: $dto->seoDescription,
                webFormId: $dto->webFormId,
                campusId: $dto->campusId,
            ),
            $institutionId,
            $userId,
        );

        Log::info('Landing page created', [
            'landing_page_uuid' => $landingPage->uuid,
            'institution_id' => $landingPage->institution_id,
            'user_id' => $userId,
        ]);

        LandingPageCreatedEvent::dispatch($landingPage);

        return $landingPage;
    }

    /** @param array<string, mixed> $data */
    public function update(LandingPage $landingPage, array $data, int $institutionId): LandingPage
    {
        if ((string) ($data['slug'] ?? '') === '' && array_key_exists('name', $data)) {
            $data['slug'] = $this->repository->generateUniqueSlug((string) $data['name'], $institutionId);
        }

        return $this->repository->update($landingPage, $this->normalisePayload($data));
    }

    public function delete(LandingPage $landingPage): void
    {
        $this->repository->softDelete($landingPage);
    }

    /** @param array<string, mixed> $trackingData */
    public function recordPublicView(LandingPage $landingPage, array $trackingData = []): void
    {
        LandingPageView::create([
            'uuid' => (string) Str::uuid(),
            'institution_id' => $landingPage->institution_id,
            'campus_id' => $landingPage->campus_id,
            'landing_page_id' => $landingPage->id,
            'viewed_at' => now(),
            'visitor_hash' => $trackingData['visitor_hash'] ?? null,
            'utm_source' => $trackingData['utm_source'] ?? null,
            'utm_medium' => $trackingData['utm_medium'] ?? null,
            'utm_campaign' => $trackingData['utm_campaign'] ?? null,
            'utm_term' => $trackingData['utm_term'] ?? null,
            'utm_content' => $trackingData['utm_content'] ?? null,
        ]);
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    private function normalisePayload(array $data): array
    {
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = array_values(array_map(static function (array $section, int $index): array {
                return [
                    'id' => isset($section['id']) && is_string($section['id']) ? $section['id'] : 'block-'.$index,
                    'type' => isset($section['type']) && is_string($section['type']) ? $section['type'] : 'value_card',
                    'order' => isset($section['order']) ? (int) $section['order'] : $index,
                    'eyebrow' => $section['eyebrow'] ?? null,
                    'title' => $section['title'] ?? null,
                    'body' => $section['body'] ?? null,
                    'metric_label' => $section['metric_label'] ?? null,
                    'metric_value' => $section['metric_value'] ?? null,
                    'question' => $section['question'] ?? null,
                    'answer' => $section['answer'] ?? null,
                ];
            }, $data['content'], array_keys($data['content'])));
        }

        if (isset($data['attribution_params']) && is_array($data['attribution_params'])) {
            $data['attribution_params'] = array_filter(
                $data['attribution_params'],
                static fn (mixed $value): bool => $value !== null && $value !== '',
            );
        }

        return $data;
    }
}