<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Events\CRM\CommunicationLogCreatedEvent;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// BRD: CRM-CC-003, CRM-CC-022 — Immutable unified communication log for all channels
class CommunicationLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'communication_logs';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // No softDeletes — communication log is immutable

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);

        // BRD: CRM-AI-001 — Trigger conversion prediction refresh on new communication entries
        static::created(static fn (self $log) => CommunicationLogCreatedEvent::dispatch($log));
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'lead_id',
        'loggable_type',
        'loggable_id',
        'channel',
        'direction',
        'template_id',
        'subject',
        'body_preview',
        'status',
        'external_id',
        'opened_at',
        'clicked_at',
        'delivered_at',
        'bounced_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'channel'      => CommunicationChannel::class,
            'direction'    => MessageDirection::class,
            'status'       => MessageStatus::class,
            'opened_at'    => 'datetime',
            'clicked_at'   => 'datetime',
            'delivered_at' => 'datetime',
            'bounced_at'   => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}
