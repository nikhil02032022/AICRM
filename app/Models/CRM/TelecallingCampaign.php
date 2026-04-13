<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\TelecallingCampaignStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-TC-006 — Calling campaign with list, agents, window, and progress aggregation
#[ObservedBy(AuditObserver::class)]
class TelecallingCampaign extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'telecalling_campaigns';

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
        'name',
        'description',
        'status',
        'start_time_window',
        'end_time_window',
        'created_by',
        'launched_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TelecallingCampaignStatus::class,
            'start_time_window' => 'datetime',
            'end_time_window' => 'datetime',
            'launched_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function agents(): HasMany
    {
        return $this->hasMany(TelecallingCampaignAgent::class, 'telecalling_campaign_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(TelecallingCampaignLead::class, 'telecalling_campaign_id');
    }

    public function diallerSessions(): HasMany
    {
        return $this->hasMany(DiallerSession::class, 'telecalling_campaign_id');
    }
}
