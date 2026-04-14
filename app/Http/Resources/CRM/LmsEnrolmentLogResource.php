<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-EI-010 — API resource for LMS enrolment log (mobile/ERP consumers)
final class LmsEnrolmentLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'            => $this->uuid,
            'lead_uuid'       => $this->lead?->uuid,
            'erp_student_id'  => $this->erp_student_id,
            'lms_provider'    => $this->lms_provider,
            'lms_user_id'     => $this->lms_user_id,
            'lms_course_id'   => $this->lms_course_id,
            'status'          => $this->status?->value,
            'status_label'    => $this->status?->label(),
            'attempt_count'   => $this->attempt_count,
            'enrolled_at'     => $this->enrolled_at?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
