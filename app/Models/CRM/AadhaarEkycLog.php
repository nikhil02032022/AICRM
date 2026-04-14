<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\AadhaarKycStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-DM-007 — Aadhaar eKYC attempt log per lead (no PII stored — DPDP compliant)
#[ObservedBy(AuditObserver::class)]
class AadhaarEkycLog extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'aadhaar_ekyc_logs';

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
        'status',
        'transaction_id',
        'otp_reference',
        'name_match',
        'kyc_complete',
        'kyc_completed_at',
        'consent_ip',
        'consent_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status'           => AadhaarKycStatus::class,
            'name_match'       => 'boolean',
            'kyc_complete'     => 'boolean',
            'kyc_completed_at' => 'datetime',
            'consent_at'       => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
