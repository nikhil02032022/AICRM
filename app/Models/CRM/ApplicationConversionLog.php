<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use Database\Factories\CRM\ApplicationConversionLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AP-016, CRM-AP-017, CRM-AP-018 — ERP Student Master conversion tracking log
class ApplicationConversionLog extends Model
{
    use HasFactory, HasUuids;

    protected static function newFactory(): ApplicationConversionLogFactory
    {
        return ApplicationConversionLogFactory::new();
    }

    protected $table = 'application_conversion_logs';

    public $timestamps = true;
    public $incrementing = false;

    /**
     * @return list<string>
     */
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
        'application_uuid',
        'lead_uuid',
        'erp_student_id',
        'converted_by_user_id',
        'status',
        'attempted_at',
        'completed_at',
        'conversion_payload',
        'erp_response',
        'error_message',
        'retry_count',
        'next_retry_at',
        'onboarding_triggered_at',
        'onboarding_status',
        'fee_migration_status',
        'fee_migration_attempted_at',
        'fee_migration_completed_at',
        'fee_migration_error',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
            'completed_at' => 'datetime',
            'next_retry_at' => 'datetime',
            'conversion_payload'      => 'json',
            'erp_response'            => 'json',
            'onboarding_triggered_at' => 'datetime',
            'onboarding_status'       => 'json',
            'fee_migration_attempted_at' => 'datetime',
            'fee_migration_completed_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_uuid', 'uuid');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_uuid', 'uuid');
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'converted_by_user_id');
    }

    /**
     * Determine if this conversion is eligible for retry.
     */
    public function isEligibleForRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3 && (
            $this->next_retry_at === null || $this->next_retry_at->isPast()
        );
    }

    /**
     * Determine if conversion was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success' && $this->erp_student_id !== null;
    }
}
