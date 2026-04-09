<?php

declare(strict_types=1);

namespace App\Repositories\CRM\WebForm;

use App\DTOs\CRM\CreateWebFormDTO;
use App\Models\CRM\WebForm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

// BRD: CRM-LC-001 — Repository interface for WebForm persistence operations
interface WebFormRepositoryInterface
{
    /**
     * Create and persist a new WebForm.
     *
     * BRD: CRM-LC-001 — New form configuration stored per institution
     */
    public function create(CreateWebFormDTO $dto, int $institutionId, string $embedToken): WebForm;

    /**
     * Find an active (non-deleted) form by its public slug within an institution.
     *
     * BRD: CRM-LC-001 — Slug must be unique per institution
     */
    public function findBySlug(string $slug, int $institutionId): ?WebForm;

    /**
     * Find an active form by slug for public submission (skips institution scope — uses raw filter).
     * Used by PublicFormController where no authenticated user is present.
     */
    public function findActiveBySlug(string $slug): ?WebForm;

    /**
     * Find a form by its UUID, throwing ModelNotFoundException if not found.
     */
    public function findByUuidOrFail(string $uuid): WebForm;

    /**
     * Update form attributes with validated data.
     *
     * BRD: CRM-LC-001 — Form configuration is mutable after creation
     */
    public function update(WebForm $form, array $data): WebForm;

    /**
     * Soft-delete the form. Hard deletes are prohibited on CRM core entities.
     */
    public function softDelete(WebForm $form): void;

    /**
     * Paginate forms for the institution (scoped by InstitutionScope global scope).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Generate a unique slug for the given name within the institution.
     * Appends numeric suffix if slug already exists (e.g. "mba-2026-2").
     */
    public function generateUniqueSlug(string $name, int $institutionId): string;
}
