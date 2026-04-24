<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\AI\TranscriptionStatus;
use App\Enums\CRM\CallDirection;
use App\Enums\CRM\CallDisposition;
use App\Enums\CRM\CallStatus;
use App\Enums\CRM\TelephonyProvider;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

// BRD: CRM-CC-018 — Call logs with consent-gated recording
// BRD: CRM-LC-010 — IVR auto-created call log
#[ObservedBy(AuditObserver::class)]
class CallLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'call_logs';

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
        'telephony_provider',
        'provider_call_id',
        'direction',
        'from_number',
        'to_number',
        'duration_seconds',
        'disposition',
        'disposition_notes',
        'call_consent_given',
        'recording_url',
        'transcript_text',
        'transcription_summary',
        'transcription_status',
        'transcription_model',
        'transcription_token_count',
        'transcribed_at',
        'status',
        'initiated_by',
        'called_at',
        'answered_at',
        'ended_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'telephony_provider'  => TelephonyProvider::class,
            'direction'           => CallDirection::class,
            'disposition'         => CallDisposition::class,
            'status'              => CallStatus::class,
            'call_consent_given'        => 'boolean',
            'transcription_summary'     => 'array',
            'transcription_status'      => TranscriptionStatus::class,
            'transcribed_at'            => 'datetime',
            'called_at'                 => 'datetime',
            'answered_at'               => 'datetime',
            'ended_at'                  => 'datetime',
        ];
    }

    // BRD: CRM-CR-006 — Phone numbers encrypted at rest (DPDP)
    public function getFromNumberAttribute(string $value): string
    {
        return Crypt::decryptString($value);
    }

    public function setFromNumberAttribute(string $value): void
    {
        $this->attributes['from_number'] = Crypt::encryptString($value);
    }

    public function getToNumberAttribute(string $value): string
    {
        return Crypt::decryptString($value);
    }

    public function setToNumberAttribute(string $value): void
    {
        $this->attributes['to_number'] = Crypt::encryptString($value);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
