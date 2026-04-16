<?php

declare(strict_types=1);

namespace App\Repositories\CRM\Application;

use App\DTOs\CRM\CreateApplicationFormTemplateDTO;
use App\Models\CRM\ApplicationFormTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class EloquentApplicationFormTemplateRepository implements ApplicationFormTemplateRepositoryInterface
{
    public function create(CreateApplicationFormTemplateDTO $dto, int $institutionId, ?int $createdBy = null): ApplicationFormTemplate
    {
        return ApplicationFormTemplate::create([
            'institution_id' => $institutionId,
            'campus_id' => $dto->campusId,
            'name' => $dto->name,
            'slug' => $dto->slug,
            'description' => $dto->description,
            'sections' => $dto->sections,
            'progression_rules' => $dto->progressionRules,
            'settings' => $dto->settings,
            'minimum_completeness_percentage' => $dto->minimumCompletenessPercentage,
            'is_active' => $dto->isActive,
            'published_at' => $dto->isActive ? now() : null,
            'created_by' => $createdBy,
        ]);
    }

    public function findByUuidOrFail(string $uuid): ApplicationFormTemplate
    {
        return ApplicationFormTemplate::where('uuid', $uuid)->firstOrFail();
    }

    /** @param array<string, mixed> $data */
    public function update(ApplicationFormTemplate $template, array $data): ApplicationFormTemplate
    {
        if (array_key_exists('sections', $data)) {
            $data['version'] = $template->version + 1;
        }

        if (array_key_exists('is_active', $data) && (bool) $data['is_active'] && $template->published_at === null) {
            $data['published_at'] = now();
        }

        $template->update($data);

        return $template->refresh();
    }

    public function softDelete(ApplicationFormTemplate $template): void
    {
        $template->delete();
    }

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = ApplicationFormTemplate::query()->with('creator:id,name');

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (!empty($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function generateUniqueSlug(string $name, int $institutionId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (
            ApplicationFormTemplate::withoutGlobalScopes()
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
