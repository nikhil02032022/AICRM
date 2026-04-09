<?php

declare(strict_types=1);

namespace App\Repositories\CRM\WebForm;

use App\DTOs\CRM\CreateWebFormDTO;
use App\Models\CRM\WebForm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

// BRD: CRM-LC-001 — Eloquent implementation of WebFormRepositoryInterface
final class EloquentWebFormRepository implements WebFormRepositoryInterface
{
    public function create(CreateWebFormDTO $dto, int $institutionId, string $embedToken): WebForm
    {
        return WebForm::create([
            'institution_id'        => $institutionId,
            'campus_id'             => $dto->campusId,
            'name'                  => $dto->name,
            'slug'                  => $dto->slug,
            'fields'                => $dto->fields,
            'is_active'             => $dto->isActive,
            'embed_token'           => $embedToken,
            'source'                => $dto->source->value,
            'redirect_url'          => $dto->redirectUrl,
            'consent_form_version'  => $dto->consentFormVersion,
            'accent_color'          => $dto->accentColor,
            'logo_url'              => $dto->logoUrl,
        ]);
    }

    public function findBySlug(string $slug, int $institutionId): ?WebForm
    {
        return WebForm::where('slug', $slug)
            ->where('institution_id', $institutionId)
            ->first();
    }

    public function findActiveBySlug(string $slug): ?WebForm
    {
        // withoutGlobalScopes() because no authenticated user in public context
        return WebForm::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();
    }

    public function findByUuidOrFail(string $uuid): WebForm
    {
        return WebForm::where('uuid', $uuid)->firstOrFail();
    }

    public function update(WebForm $form, array $data): WebForm
    {
        $form->update($data);

        return $form->refresh();
    }

    public function softDelete(WebForm $form): void
    {
        $form->delete();
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = WebForm::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function generateUniqueSlug(string $name, int $institutionId): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (
            WebForm::withoutGlobalScopes()
                ->where('institution_id', $institutionId)
                ->where('slug', $slug)
                ->whereNull('deleted_at')
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
