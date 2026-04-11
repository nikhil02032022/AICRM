<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-LC-020 — Value object representing a matched student/alumni record from the A2A ERP Student Master
final readonly class ErpStudentDTO
{
    public function __construct(
        /** UUID from the A2A ERP Student Master table */
        public string $studentUuid,

        /** Enrolment/registration number (not PII — institutional identifier) */
        public string $enrollmentNo,

        /** Programme/course the student was admitted to */
        public string $admittedCourse,

        /** True if the student has graduated (is in Alumni module) */
        public bool $isAlumni,
    ) {}

    /** Construct from ERP API JSON response array */
    public static function fromArray(array $data): self
    {
        return new self(
            studentUuid: (string) $data['uuid'],
            enrollmentNo: (string) ($data['enrollment_no'] ?? $data['enrollmentNo'] ?? ''),
            admittedCourse: (string) ($data['admitted_course'] ?? $data['admittedCourse'] ?? ''),
            isAlumni: (bool) ($data['is_alumni'] ?? $data['isAlumni'] ?? false),
        );
    }
}
