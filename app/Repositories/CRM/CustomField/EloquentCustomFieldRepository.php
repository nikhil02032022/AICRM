<?php

declare(strict_types=1);

namespace App\Repositories\CRM\CustomField;

use App\Models\CRM\CustomField;
use App\Models\CRM\CustomFieldValue;
use Illuminate\Database\Eloquent\Collection;

final class EloquentCustomFieldRepository implements CustomFieldRepositoryInterface
{
    // BRD: CRM-EC-005 — Retrieve all active fields for an entity type within an institution
    public function getActiveFieldsForEntity(int $institutionId, string $entity): Collection
    {
        return CustomField::where('institution_id', $institutionId)
            ->where('entity', $entity)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    public function findByUuidOrFail(string $uuid): CustomField
    {
        return CustomField::where('uuid', $uuid)->firstOrFail();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): CustomField
    {
        return CustomField::create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(CustomField $field, array $data): CustomField
    {
        $field->update($data);

        return $field->fresh();
    }

    public function delete(CustomField $field): void
    {
        $field->delete();
    }

    // BRD: CRM-EC-005 — Idempotent upsert for a single field value on any entity record
    public function upsertValue(int $institutionId, int $customFieldId, string $entityType, int $entityId, ?string $value): CustomFieldValue
    {
        /** @var CustomFieldValue $cfv */
        $cfv = CustomFieldValue::firstOrNew([
            'custom_field_id' => $customFieldId,
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
        ]);

        $cfv->institution_id = $institutionId;
        $cfv->value          = $value;
        $cfv->save();

        return $cfv;
    }

    /** @return array<string, string|null> */
    public function getValuesForEntity(string $entityType, int $entityId): array
    {
        return CustomFieldValue::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with('customField:id,field_key')
            ->get()
            ->mapWithKeys(fn (CustomFieldValue $v) => [$v->customField->field_key => $v->value])
            ->all();
    }
}
