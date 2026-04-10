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

// BRD: CRM-CC-002 — Email bulk campaign entity
#[ObservedBy(AuditObserver::class)]
class EmailCampaign extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'email_campaigns';

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
        'subject',
        'template_id',
        'from_name',
        'from_email',
        'status',
        'scheduled_at',
        'recipient_filter',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'unsubscribed_count',
        'created_by',
        'sent_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status'           => CampaignStatus::class,
            'scheduled_at'     => 'datetime',
            'sent_at'          => 'datetime',
            'recipient_filter' => 'array',
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
