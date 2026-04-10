<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\CounsellingSessionStatus;
use App\Enums\CRM\SessionType;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-EC-015 — Counselling session appointment record
// BRD: CRM-EC-016 — Supports both internal staff scheduling and public booking
// BRD: CRM-EC-017 — Reminder flags tracked here
#[ObservedBy(AuditObserver::class)]
class CounsellingSession extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'counselling_sessions';

    public function uniqueIds(): array
    {
        return ['id'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'lead_id',
        'counsellor_id',
        'availability_slot_id',
        'session_type',
        'status',
        'mode',
        'scheduled_at',
        'completed_at',
        'pre_session_notes',
        'post_session_notes',
        'reminder_24h_sent',
        'reminder_1h_sent',
        'booking_token',
        'booking_token_expires_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'session_type' => SessionType::class,
            'status' => CounsellingSessionStatus::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'booking_token_expires_at' => 'datetime',
            'reminder_24h_sent' => 'boolean',
            'reminder_1h_sent' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counsellor_id');
    }

    public function availabilitySlot(): BelongsTo
    {
        return $this->belongsTo(CounsellorAvailabilitySlot::class, 'availability_slot_id');
    }
}
