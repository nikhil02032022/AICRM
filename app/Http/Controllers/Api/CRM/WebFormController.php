<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\CRM;

use App\DTOs\CRM\CreateWebFormDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CRM\StoreWebFormRequest;
use App\Http\Requests\Api\CRM\UpdateWebFormRequest;
use App\Http\Resources\CRM\WebFormResource;
use App\Models\CRM\WebForm;
use App\Models\User;
use App\Repositories\CRM\WebForm\WebFormRepositoryInterface;
use App\Services\CRM\WebForm\WebFormService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

// BRD: CRM-LC-001 — API controller for WebForm management (Sanctum auth — external consumers only)
// Consumers: React Native app, A2A ERP integrations — NOT the CRM web application
final class WebFormController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly WebFormService $service,
        private readonly WebFormRepositoryInterface $repository,
    ) {}

    /**
     * BRD: CRM-LC-001 — List all web forms for the authenticated institution.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('crm.forms.view');

        $forms = $this->repository->paginate(
            filters: $request->only(['is_active', 'search']),
            perPage: (int) $request->get('per_page', 20),
        );

        return WebFormResource::collection($forms);
    }

    /**
     * BRD: CRM-LC-001 — Create a new web form.
     */
    public function store(StoreWebFormRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $dto = CreateWebFormDTO::fromRequest($request->validated());

        // Auto-generate slug if not provided
        if (empty($dto->slug)) {
            $dto = new CreateWebFormDTO(
                name: $dto->name,
                slug: $this->service->generateUniqueSlug($dto->name, $user->institution_id),
                fields: $dto->fields,
                source: $dto->source,
                isActive: $dto->isActive,
                redirectUrl: $dto->redirectUrl,
                consentFormVersion: $dto->consentFormVersion,
                accentColor: $dto->accentColor,
                logoUrl: $dto->logoUrl,
                campusId: $dto->campusId,
            );
        }

        $form = $this->service->create($dto, $user->institution_id);

        return response()->json(
            $this->success(new WebFormResource($form), 'Web form created successfully.'),
            201,
        );
    }

    /**
     * BRD: CRM-LC-001 — Get a single web form by UUID.
     */
    public function show(WebForm $form): WebFormResource
    {
        Gate::authorize('crm.forms.view');

        return new WebFormResource($form);
    }

    /**
     * BRD: CRM-LC-001 — Update web form configuration.
     */
    public function update(UpdateWebFormRequest $request, WebForm $form): JsonResponse
    {
        $updated = $this->service->update($form, $request->validated());

        return response()->json(
            $this->success(new WebFormResource($updated), 'Web form updated successfully.'),
        );
    }

    /**
     * BRD: CRM-LC-001 — Soft-delete a web form.
     */
    public function destroy(WebForm $form): JsonResponse
    {
        Gate::authorize('crm.forms.delete');

        $this->service->delete($form);

        return response()->json(
            $this->success(null, 'Web form deleted successfully.'),
        );
    }

    /**
     * BRD: CRM-LC-009 — Download QR code PNG for a web form's public UTM URL.
     */
    public function qr(WebForm $form): Response
    {
        Gate::authorize('crm.forms.view');

        $png = $this->service->generateQrCode($form);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="qr-'.$form->slug.'.png"',
        ]);
    }
}
