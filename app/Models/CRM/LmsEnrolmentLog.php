<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\LmsEnrolmentStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-EI-010 — LMS enrolment trigger log per admitted student
#[ObservedBy(AuditObserver::class)]
class LmsEnrolmentLog extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'lms_enrolment_logs';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'lead_id',
        'erp_student_id',
        'lms_provider',
        'lms_user_id',
        'lms_course_id',
        'status',
        'error_message',
        'enrolled_at',
        'attempt_count',
    ];

    protected function casts(): array
    {
        return [
            'status'      => LmsEnrolmentStatus::class,
            'enrolled_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
