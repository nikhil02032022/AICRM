<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\AgentCommsChannel;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-AG-008 — Agent bulk communications log — email/WhatsApp/SMS blasts to agent network
#[ObservedBy(AuditObserver::class)]
class AgentCommsLog extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'agent_comms_logs';

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
        'sent_by',
        'channel',
        'subject',
        'message_body',
        'recipient_agent_ids',
        'recipient_count',
        'delivered_count',
        'failed_count',
        'status',
        'sent_at',
        'opt_out_respected',
    ];

    protected function casts(): array
    {
        return [
            'channel'             => AgentCommsChannel::class,
            'recipient_agent_ids' => 'array',
            'opt_out_respected'   => 'boolean',
            'sent_at'             => 'datetime',
        ];
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
