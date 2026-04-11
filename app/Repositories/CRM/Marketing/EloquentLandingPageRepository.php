<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Marketing;

use App\DTOs\CRM\CreateLandingPageDTO;
use App\Enums\CRM\LandingPageStatus;
use App\Models\CRM\LandingPageView;
use App\Models\CRM\LandingPage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class EloquentLandingPageRepository implements LandingPageRepositoryInterface
{
    public function create(CreateLandingPageDTO $dto, int $institutionId, int $userId): LandingPage
    {
        return LandingPage::create([
            'institution_id' => $institutionId,
            'campus_id' => $dto->campusId,
            'web_form_id' => $dto->webFormId,
            'created_by' => $userId,
            'name' => $dto->name,
            'slug' => $dto->slug,
            'status' => $dto->status->value,
            'theme_variant' => $dto->themeVariant,
            'headline' => $dto->headline,
            'subheadline' => $dto->subheadline,
            'hero_image_url' => $dto->heroImageUrl,
            'cta_label' => $dto->ctaLabel,
            'cta_secondary_label' => $dto->ctaSecondaryLabel,
            'content' => $dto->content,
            'attribution_params' => $dto->attributionParams,
            'seo_title' => $dto->seoTitle,
            'seo_description' => $dto->seoDescription,
            'published_at' => $dto->status === LandingPageStatus::PUBLISHED ? now() : null,
        ]);
    }

    public function update(LandingPage $landingPage, array $data): LandingPage
    {
        if (array_key_exists('status', $data)) {
            $status = $data['status'] instanceof LandingPageStatus
                ? $data['status']
                : LandingPageStatus::from((string) $data['status']);

            $data['status'] = $status->value;
            $data['published_at'] = $status === LandingPageStatus::PUBLISHED
                ? ($landingPage->published_at ?? now())
                : null;
        }

        $landingPage->update($data);

        return $landingPage->fresh(['webForm', 'creator']);
    }

    public function softDelete(LandingPage $landingPage): void
    {
        $landingPage->delete();
    }

    public function findByUuidOrFail(string $uuid): LandingPage
    {
        return LandingPage::with(['webForm', 'creator'])->where('uuid', $uuid)->firstOrFail();
    }

    public function findPublishedBySlug(string $slug): ?LandingPage
    {
        return LandingPage::withoutGlobalScopes()
            ->with('webForm')
            ->where('slug', $slug)
            ->where('status', LandingPageStatus::PUBLISHED->value)
            ->whereNull('deleted_at')
            ->first();
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = LandingPage::query()
            ->with(['webForm', 'creator'])
            ->withCount('landingPageViews')
            ->addSelect([
                'view_count_last_7d' => LandingPageView::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('landing_page_views.landing_page_id', 'landing_pages.id')
                    ->where('viewed_at', '>=', now()->subDays(7)),
            ]);

        if (! empty($filters['search'])) {
            $query->where(function ($builder) use ($filters): void {
                $builder->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('headline', 'like', '%'.$filters['search'].'%')
                    ->orWhere('slug', 'like', '%'.$filters['search'].'%');
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['web_form_id'])) {
            $query->where('web_form_id', (int) $filters['web_form_id']);
        }

        return $query->orderByDesc('updated_at')->paginate($perPage);
    }

    public function generateUniqueSlug(string $name, int $institutionId): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'landing-page';
        $slug = $base;
        $counter = 2;

        while (
            LandingPage::withoutGlobalScopes()
                ->where('institution_id', $institutionId)
                ->where('slug', $slug)
                ->whereNull('deleted_at')
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}