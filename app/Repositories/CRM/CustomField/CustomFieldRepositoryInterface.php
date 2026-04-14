<?php

declare(strict_types=1);

namespace App\Repositories\CRM\CustomField;

use App\Models\CRM\CustomField;
use App\Models\CRM\CustomFieldValue;
use Illuminate\Database\Eloquent\Collection;

interface CustomFieldRepositoryInterface
{
    // BRD: CRM-EC-005 — Retrieve all active fields for a given entity within an institution
    /** @return Collection<int, CustomField> */
    public function getActiveFieldsForEntity(int $institutionId, string $entity): Collection;

    public function findByUuidOrFail(string $uuid): CustomField;

    /** @param array<string, mixed> $data */
    public function create(array $data): CustomField;

    /** @param array<string, mixed> $data */
    public function update(CustomField $field, array $data): CustomField;

    public function delete(CustomField $field): void;

    // BRD: CRM-EC-005 — Upsert a single custom field value for a polymorphic entity
    public function upsertValue(int $institutionId, int $customFieldId, string $entityType, int $entityId, ?string $value): CustomFieldValue;

    /**
     * Return all values for a given entity record, keyed by field_key.
     *
     * @return array<string, string|null>
     */
    public function getValuesForEntity(string $entityType, int $entityId): array;
}
