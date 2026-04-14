<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ProgrammeInterestStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

// BRD: CRM-EC-002 — Pivot model for per-programme status tracking on a lead
class LeadProgrammeInterest extends Pivot
{
    protected $table = 'lead_programme_interests';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = [
        'lead_id',
        'crm_programme_id',
        'is_primary',
        'status',
        'notes',
        'preferred_intake',
    ];

    /** @var array<string, mixed> */
    protected $casts = [
        'is_primary' => 'boolean',
        'status'     => ProgrammeInterestStatus::class,
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(CrmProgramme::class, 'crm_programme_id');
    }
}
