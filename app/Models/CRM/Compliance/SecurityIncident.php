<?php

declare(strict_types=1);

namespace App\Models\CRM\Compliance;

use App\Enums\CRM\Compliance\SecurityIncidentStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityIncident extends Model
{
    protected $table = 'security_incidents';

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'reported_by',
        'incident_type',
        'description',
        'detected_at',
        'notified_at',
        'status',
        'documentation_json',
    ];

    protected function casts(): array
    {
        return [
            'status'             => SecurityIncidentStatus::class,
            'detected_at'        => 'datetime',
            'notified_at'        => 'datetime',
            'documentation_json' => 'array',
        ];
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reported_by');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Institution::class);
    }

    public function isWithin72Hours(): bool
    {
        return $this->detected_at !== null
            && now()->diffInHours($this->detected_at) <= 72;
    }
}
