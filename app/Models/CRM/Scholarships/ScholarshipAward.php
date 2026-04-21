<?php

declare(strict_types=1);

namespace App\Models\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ApprovalStage;
use App\Enums\CRM\Scholarships\ScholarshipAwardStatus;
use App\Models\CRM\Application;
use App\Models\CRM\Lead;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-FM-008
class ScholarshipAward extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'scholarship_awards';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid', 'institution_id', 'campus_id',
        'application_uuid', 'lead_uuid', 'scholarship_category_id',
        'amount', 'status', 'current_stage',
        'requested_by', 'rejection_reason',
        'counsellor_submitted_at', 'manager_approved_at',
        'finance_approved_at', 'rejected_at', 'withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => ScholarshipAwardStatus::class,
            'current_stage' => ApprovalStage::class,
            'counsellor_submitted_at' => 'datetime',
            'manager_approved_at' => 'datetime',
            'finance_approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_uuid', 'uuid');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_uuid', 'uuid');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ScholarshipCategory::class, 'scholarship_category_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ScholarshipApproval::class);
    }
}
