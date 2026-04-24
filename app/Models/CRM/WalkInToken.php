<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\Counselling\WalkInTokenStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

// BRD: CRM-EC-019 — Walk-in visitor token; institution + campus scoped; daily sequential numbering
#[ObservedBy(AuditObserver::class)]
class WalkInToken extends Model
{
    use HasUuids;

    protected $table = 'walk_in_tokens';

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
        'campus_id',
        'token_number',
        'token_date',
        'lead_id',
        'visitor_name',
        'visitor_mobile',
        'programme_interest',
        'status',
        'counsellor_id',
        'called_at',
        'served_at',
        'skipped_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => WalkInTokenStatus::class,
            'token_date' => 'date',
            'called_at' => 'datetime',
            'served_at' => 'datetime',
            'skipped_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function counsellor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counsellor_id');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** @param Builder<WalkInToken> $query */
    public function scopeForCampusToday(Builder $query, int $campusId): Builder
    {
        return $query->where('campus_id', $campusId)
            ->whereDate('token_date', Carbon::today());
    }

    /** @param Builder<WalkInToken> $query */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', WalkInTokenStatus::WAITING);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the next sequential token number for today at the given campus.
     * Concurrent requests are safely handled by the DB MAX() + 1 strategy inside a transaction
     * in WalkInQueueService.
     */
    public static function nextTokenNumber(int $campusId): int
    {
        $max = static::withoutGlobalScopes()
            ->where('campus_id', $campusId)
            ->whereDate('token_date', Carbon::today())
            ->max('token_number');

        return ($max ?? 0) + 1;
    }
}
