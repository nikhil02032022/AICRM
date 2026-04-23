<?php

declare(strict_types=1);

namespace App\Models\CRM\Agents;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

// BRD: CRM-AG-003 — Agent portal session token store (mirrors PortalSession)
class AgentSession extends Model
{
    protected $table = 'agent_sessions';

    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'agent_id',
        'token_hash',
        'expires_at',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
