<?php

declare(strict_types=1);

namespace App\Services\CRM\Documents;

use App\Enums\CRM\Documents\DocumentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Documents\DocumentChecklistItem;
use Illuminate\Support\Facades\Cache;

// BRD: CRM-DM-010 — Document completeness score per applicant.
final class DocumentCompletenessCalculator
{
    public function __construct(private DocumentChecklistService $checklistService)
    {
    }

    public function scoreFor(Application $application): float
    {
        $ttl = (int) config('crm_documents.completeness.cache_ttl', 120);

        return (float) Cache::remember(
            $this->cacheKey($application),
            $ttl,
            fn () => $this->compute($application)
        );
    }

    public function invalidate(Application $application): void
    {
        Cache::forget($this->cacheKey($application));
    }

    private function compute(Application $application): float
    {
        $checklist = $this->checklistService->resolveForProgramme($application->institution_id, $application->programme_id);
        if (! $checklist || $checklist->items->isEmpty()) {
            return 0.0;
        }

        $mandatoryWeight = (float) config('crm_documents.completeness.mandatory_weight', 1.0);
        $optionalWeight  = (float) config('crm_documents.completeness.optional_weight', 0.25);

        $docs = ApplicationDocument::withoutGlobalScopes()
            ->where('application_uuid', $application->uuid)
            ->get()
            ->keyBy('document_checklist_item_id');

        $totalWeight = 0.0;
        $earned = 0.0;

        /** @var DocumentChecklistItem $item */
        foreach ($checklist->items as $item) {
            $weight = $item->is_mandatory ? $mandatoryWeight : $optionalWeight;
            $totalWeight += $weight;

            $doc = $docs->get($item->id);
            if ($doc && $doc->status === DocumentStatus::VERIFIED) {
                $earned += $weight;
            }
        }

        if ($totalWeight <= 0) {
            return 0.0;
        }

        return round(($earned / $totalWeight) * 100.0, 2);
    }

    private function cacheKey(Application $application): string
    {
        return 'doc_completeness:'.$application->uuid;
    }
}
