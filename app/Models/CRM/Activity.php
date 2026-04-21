<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ActivityType;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-EC-004 — Activity timeline entry for all CRM interaction channels
// DPDP: body and metadata must never contain raw PII (mobile, email, Aadhaar)
#[ObservedBy(AuditObserver::class)]
class Activity extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'activities';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // BRD: NFR-MT-001 — Institution-scoped, but bypassed in repository queries for cross-institution admin
    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'subject_type',
        'subject_id',
        'type',
        'direction',
        'channel',
        'body',
        'performed_by_id',
        'metadata',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'metadata' => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
