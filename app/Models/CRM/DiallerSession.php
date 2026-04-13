<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\DiallerSessionStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-TC-001 — Power/auto-dialler session aggregate model
#[ObservedBy(AuditObserver::class)]
class DiallerSession extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'dialler_sessions';

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
        'telecalling_campaign_id',
        'started_by',
        'campaign_name',
        'status',
        'total_leads',
        'queued_calls',
        'placed_calls',
        'skipped_calls',
        'failed_calls',
        'started_at',
        'last_dialled_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DiallerSessionStatus::class,
            'telecalling_campaign_id' => 'integer',
            'total_leads' => 'integer',
            'queued_calls' => 'integer',
            'placed_calls' => 'integer',
            'skipped_calls' => 'integer',
            'failed_calls' => 'integer',
            'started_at' => 'datetime',
            'last_dialled_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DiallerLog::class, 'dialler_session_id');
    }

    public function telecallingCampaign(): BelongsTo
    {
        return $this->belongsTo(TelecallingCampaign::class, 'telecalling_campaign_id');
    }
}
