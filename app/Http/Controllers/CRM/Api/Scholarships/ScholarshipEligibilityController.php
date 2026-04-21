<?php

declare(strict_types=1);

namespace App\Http\Controllers\CRM\Api\Scholarships;

use App\Http\Controllers\Controller;
use App\Models\CRM\Application;
use App\Services\CRM\Scholarships\ScholarshipEligibilityEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// BRD: CRM-FM-007
class ScholarshipEligibilityController extends Controller
{
    public function __construct(private readonly ScholarshipEligibilityEvaluator $evaluator) {}

    public function evaluate(Request $request): JsonResponse
    {
        $request->validate([
            'application_uuid' => ['required', 'uuid', 'exists:applications,uuid'],
        ]);

        $application = Application::withoutGlobalScopes()
            ->where('uuid', $request->string('application_uuid'))
            ->firstOrFail();

        $matches = $this->evaluator->evaluate($application)
            ->map(fn ($c) => [
                'id' => $c->id,
                'uuid' => $c->uuid,
                'name' => $c->name,
                'type' => $c->type?->value,
                'computation' => $c->computation,
                'value' => (float) $c->value,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $matches]);
    }
}
