<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\CRM;

use App\Enums\CRM\CustomFieldEntity;
use App\Enums\CRM\CustomFieldType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreCustomFieldRequest;
use App\Http\Requests\Api\CRM\UpdateCustomFieldRequest;
use App\Models\CRM\CustomField;
use App\Services\CRM\CustomField\CustomFieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// BRD: CRM-EC-005 — Web controller: custom field configuration for institution admins
final class CustomFieldWebController extends Controller
{
    public function __construct(
        private readonly CustomFieldService $service,
    ) {}

    // BRD: CRM-EC-005 — List all custom fields grouped by entity
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CustomField::class);

        $institutionId = $request->user()->institution_id;
        $entity        = $request->query('entity', CustomFieldEntity::LEAD->value);

        $fields = $this->service->getActiveFields($institutionId, $entity);

        return view('crm.settings.custom-fields.index', [
            'fields'         => $fields,
            'entityOptions'  => CustomFieldEntity::optionsForSelect(),
            'typeOptions'    => CustomFieldType::optionsForSelect(),
            'currentEntity'  => $entity,
        ]);
    }

    // BRD: CRM-EC-005 — Create a new custom field; returns JSON for Alpine.js modal
    public function store(StoreCustomFieldRequest $request): JsonResponse
    {
        Gate::authorize('create', CustomField::class);

        $field = $this->service->create(
            $request->validated(),
            $request->user()->institution_id,
        );

        return response()->json([
            'success' => true,
            'data'    => ['uuid' => $field->uuid, 'label' => $field->label],
            'message' => 'Custom field created successfully.',
        ], 201);
    }

    // BRD: CRM-EC-005 — Update label/type/options/flags; field_key is immutable
    public function update(UpdateCustomFieldRequest $request, CustomField $customField): JsonResponse
    {
        Gate::authorize('update', $customField);

        $updated = $this->service->update($customField, $request->validated());

        return response()->json([
            'success' => true,
            'data'    => ['uuid' => $updated->uuid, 'label' => $updated->label],
            'message' => 'Custom field updated.',
        ]);
    }

    // BRD: CRM-EC-005 — Soft-delete; audit log written by AuditObserver
    public function destroy(CustomField $customField): JsonResponse
    {
        Gate::authorize('delete', $customField);

        $this->service->delete($customField);

        return response()->json([
            'success' => true,
            'message' => 'Custom field deleted.',
        ]);
    }
}
