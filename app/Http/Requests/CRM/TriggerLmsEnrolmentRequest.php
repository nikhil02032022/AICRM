<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-EI-010 — Validation for triggering LMS enrolment
final class TriggerLmsEnrolmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'erp_student_id' => ['required', 'string', 'max:80'],
            'lead_uuid'      => ['required', 'string', 'uuid', 'exists:leads,uuid'],
            'lms_provider'   => ['required', 'string', 'in:camplus,moodle'],
            'lms_course_id'  => ['required', 'string', 'max:80'],
        ];
    }
}
