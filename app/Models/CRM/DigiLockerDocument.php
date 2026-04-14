<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\DigiLockerStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-DM-006 — DigiLocker document record per applicant/lead
#[ObservedBy(AuditObserver::class)]
class DigiLockerDocument extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'digilocker_documents';

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
        'document_type',
        'digilocker_request_id',
        'digilocker_uri',
        'storage_path',
        'is_verified',
        'verified_at',
        'error_message',
        'consent_record_id',
    ];

    protected function casts(): array
    {
        return [
            'status'      => DigiLockerStatus::class,
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function consentRecord(): BelongsTo
    {
        return $this->belongsTo(ConsentRecord::class);
    }
}
