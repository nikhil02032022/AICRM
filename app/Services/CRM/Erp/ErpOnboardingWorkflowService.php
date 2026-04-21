<?php

declare(strict_types=1);

namespace App\Services\CRM\Erp;

use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use Illuminate\Support\Facades\Log;

// BRD: CRM-AP-018 — Triggers ERP onboarding sub-workflows after successful student conversion
final class ErpOnboardingWorkflowService
{
    /**
     * Trigger all onboarding workflows for a newly converted ERP student.
     *
     * Calls ID card generation, LMS enrolment, and hostel allocation prompt.
     * Never throws — each failure is logged and captured in the results array.
     *
     * @return array{id_card: bool, lms_enrolment: bool, hostel_prompt: bool}
     */
    public function triggerAll(string $erpStudentId, Application $application): array
    {
        $client = ErpApiClient::forInstitution($application->institution_id);
        $programmeCode = $this->resolveProgrammeCode($application);

        $results = [
            'id_card'        => false,
            'lms_enrolment'  => false,
            'hostel_prompt'  => false,
        ];

        try {
            $results['id_card'] = $client->triggerIdCardGeneration($erpStudentId);
        } catch (\Throwable $e) {
            Log::warning('ErpOnboardingWorkflowService: id_card trigger threw.', ['error' => $e->getMessage()]);
        }

        try {
            $results['lms_enrolment'] = $client->triggerLmsEnrolment($erpStudentId, $programmeCode);
        } catch (\Throwable $e) {
            Log::warning('ErpOnboardingWorkflowService: lms_enrolment trigger threw.', ['error' => $e->getMessage()]);
        }

        try {
            $results['hostel_prompt'] = $client->triggerHostelAllocationPrompt($erpStudentId);
        } catch (\Throwable $e) {
            Log::warning('ErpOnboardingWorkflowService: hostel_prompt trigger threw.', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    private function resolveProgrammeCode(Application $application): string
    {
        $draft = $application->draft;

        if ($draft !== null && ! empty($draft->selected_programme_uuids)) {
            $programme = CrmProgramme::withoutGlobalScopes()
                ->where('uuid', $draft->selected_programme_uuids[0])
                ->first();

            return $programme?->code ?? '';
        }

        return '';
    }
}
