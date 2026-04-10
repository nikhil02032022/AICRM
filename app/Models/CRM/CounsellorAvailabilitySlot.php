<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// BRD: CRM-EC-015 — Counsellor availability slot definition
// BRD: CRM-EC-016 — Slot model used by public booking feature
#[ObservedBy(AuditObserver::class)]
class CounsellorAvailabilitySlot extends Model
{
    use HasUuids;

    protected $table = 'counsellor_availability_slots';

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
        'counsellor_id',
        'day_of_week',
        'slot_date',
        'start_time',
        'end_time',
        'slot_duration_minutes',
        'is_active',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
            'is_active' => 'boolean',
            'day_of_week' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counsellor_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CounsellingSession::class, 'availability_slot_id');
    }
}
