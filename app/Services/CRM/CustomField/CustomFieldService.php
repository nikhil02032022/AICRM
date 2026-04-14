<?php

declare(strict_types=1);

namespace App\Services\CRM\CustomField;

use App\Enums\CRM\CustomFieldEntity;
use App\Models\CRM\CustomField;
use App\Models\CRM\CustomFieldValue;
use App\Repositories\CRM\CustomField\CustomFieldRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// BRD: CRM-EC-005 — Business logic for institution-level custom field management
final class CustomFieldService
{
    public function __construct(
        private readonly CustomFieldRepositoryInterface $repository,
    ) {}

    /**
     * BRD: CRM-EC-005 — Return all active custom fields for an entity within an institution.
     *
     * @return Collection<int, CustomField>
     */
    public function getActiveFields(int $institutionId, string $entity): Collection
    {
        return $this->repository->getActiveFieldsForEntity($institutionId, $entity);
    }

    /**
     * BRD: CRM-EC-005 — Create a new custom field definition; auto-derives field_key from label.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $institutionId): CustomField
    {
        // Derive snake_case field key from label if not supplied
        $fieldKey = $data['field_key'] ?? Str::snake(Str::limit($data['label'], 80, ''));

        $data['field_key']     = $fieldKey;
        $data['institution_id'] = $institutionId;

        // Field-level RBAC auditing is handled by AuditObserver on the model.
        Log::info('Custom field created', [
            'institution_id' => $institutionId,
            'entity'         => $data['entity'] ?? 'unknown',
            'field_key'      => $fieldKey,
        ]);

        return $this->repository->create($data);
    }

    /**
     * BRD: CRM-EC-005 — Update an existing custom field definition.
     *
     * @param array<string, mixed> $data
     */
    public function update(CustomField $field, array $data): CustomField
    {
        // field_key is immutable after creation to preserve existing stored values
        unset($data['field_key'], $data['entity']);

        return $this->repository->update($field, $data);
    }

    /**
     * BRD: CRM-EC-005 — Soft-delete a custom field; values are cascade-deleted.
     */
    public function delete(CustomField $field): void
    {
        $this->repository->delete($field);
    }

    /**
     * BRD: CRM-EC-005 — Save custom field values for any entity (Lead / Application).
     *
     * @param array<string, string|null> $values  Keyed by field_key
     */
    public function saveValues(int $institutionId, string $entityType, int $entityId, array $values): void
    {
        // Load field definitions to resolve field_key → id
        $entityEnum = $this->resolveEntityEnum($entityType);
        $fields     = $this->repository->getActiveFieldsForEntity($institutionId, $entityEnum->value)
            ->keyBy('field_key');

        foreach ($values as $fieldKey => $value) {
            if (!$fields->has($fieldKey)) {
                continue; // Skip unknown keys silently
            }

            /** @var CustomField $field */
            $field = $fields->get($fieldKey);

            if ($field->is_required && ($value === null || $value === '')) {
                throw ValidationException::withMessages([
                    "custom_fields.{$fieldKey}" => "The {$field->label} field is required.",
                ]);
            }

            $this->repository->upsertValue($institutionId, $field->id, $entityType, $entityId, $value);
        }
    }

    /**
     * BRD: CRM-EC-005 — Return custom field values for a given entity record.
     *
     * @return array<string, string|null>
     */
    public function getValues(string $entityType, int $entityId): array
    {
        return $this->repository->getValuesForEntity($entityType, $entityId);
    }

    private function resolveEntityEnum(string $entityType): CustomFieldEntity
    {
        return match (true) {
            str_ends_with($entityType, 'Lead')        => CustomFieldEntity::LEAD,
            str_ends_with($entityType, 'Application') => CustomFieldEntity::APPLICATION,
            default                                    => CustomFieldEntity::LEAD,
        };
    }
}
