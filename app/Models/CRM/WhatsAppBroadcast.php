<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\CampaignStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-CC-015 — WhatsApp broadcast campaign entity
#[ObservedBy(AuditObserver::class)]
class WhatsAppBroadcast extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'whatsapp_broadcasts';

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
        'name',
        'template_id',
        'recipient_filter',
        'lead_count',
        'dispatched_count',
        'status',
        'launched_at',
        'created_by',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status'           => CampaignStatus::class,
            'recipient_filter' => 'array',
            'launched_at'      => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
