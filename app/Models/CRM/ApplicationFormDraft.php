<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ApplicationFormDraftStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-AP-003 — Application draft entity for save-and-resume
#[ObservedBy(AuditObserver::class)]
class ApplicationFormDraft extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'application_form_drafts';

    /** @return list<string> */
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
        'application_form_template_id',
        'resume_token',
        'status',
        'current_section_id',
        'last_completed_section_order',
        'progress_percentage',
        'form_data',
        'selected_programme_uuids',
        'application_fee_amount',
        'application_fee_currency',
        'application_fee_status',
        'application_fee_transaction_reference',
        'application_fee_gateway',
        'application_fee_paid_at',
        'last_saved_at',
        'expires_at',
        'submitted_at',
        'created_by',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => ApplicationFormDraftStatus::class,
            'progress_percentage' => 'integer',
            'last_completed_section_order' => 'integer',
            'form_data' => 'encrypted:array',
            'selected_programme_uuids' => 'array',
            'application_fee_amount' => 'decimal:2',
            'last_saved_at' => 'datetime',
            'expires_at' => 'datetime',
            'application_fee_paid_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ApplicationFormTemplate::class, 'application_form_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
