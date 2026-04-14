<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreCustomFieldRequest;
use App\Http\Requests\Api\CRM\UpdateCustomFieldRequest;
use App\Http\Resources\CRM\CustomFieldResource;
use App\Models\CRM\CustomField;
use App\Services\CRM\CustomField\CustomFieldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

// BRD: CRM-EC-005 — API controller for external integrations (React Native / ERP)
final class CustomFieldController extends Controller
{
    public function __construct(
        private readonly CustomFieldService $service,
    ) {}

    /** GET /api/v1/crm/custom-fields?entity=lead */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', CustomField::class);

        $entity = $request->string('entity', 'lead')->toString();
        $fields = $this->service->getActiveFields(
            $request->user()->institution_id,
            $entity,
        );

        return CustomFieldResource::collection($fields);
    }

    /** POST /api/v1/crm/custom-fields */
    public function store(StoreCustomFieldRequest $request): JsonResponse
    {
        Gate::authorize('create', CustomField::class);

        $field = $this->service->create(
            $request->validated(),
            $request->user()->institution_id,
        );

        return (new CustomFieldResource($field))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/v1/crm/custom-fields/{customField:uuid} */
    public function show(CustomField $customField): CustomFieldResource
    {
        Gate::authorize('view', $customField);

        return new CustomFieldResource($customField);
    }

    /** PUT /api/v1/crm/custom-fields/{customField:uuid} */
    public function update(UpdateCustomFieldRequest $request, CustomField $customField): CustomFieldResource
    {
        Gate::authorize('update', $customField);

        $updated = $this->service->update($customField, $request->validated());

        return new CustomFieldResource($updated);
    }

    /** DELETE /api/v1/crm/custom-fields/{customField:uuid} */
    public function destroy(CustomField $customField): JsonResponse
    {
        Gate::authorize('delete', $customField);

        $this->service->delete($customField);

        return response()->json(['success' => true, 'message' => 'Custom field deleted.']);
    }
}
