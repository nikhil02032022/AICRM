<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-LC-006 — Persisted chat-led enquiry payload for attribution + audit trail
final class ChatLead extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'chat_leads';

    /** @return list<string> */
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
        'assigned_to',
        'session_id',
        'handoff_status',
        'visitor_name',
        'source_url',
        'transcript',
        'attribution_params',
        'consent_given',
        'consent_timestamp',
        'consent_ip',
        'consent_form_version',
        'metadata',
        'processed_at',
        'first_response_at',
        'last_message_at',
        'inbound_messages',
        'outbound_messages',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'transcript' => 'encrypted:array',
            'attribution_params' => 'array',
            'metadata' => 'encrypted:array',
            'consent_given' => 'boolean',
            'consent_timestamp' => 'datetime',
            'processed_at' => 'datetime',
            'first_response_at' => 'datetime',
            'last_message_at' => 'datetime',
            'inbound_messages' => 'integer',
            'outbound_messages' => 'integer',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
