<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\CampaignStatus;
use App\Enums\CRM\SmsGateway;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-CC-006 — SMS bulk campaign entity
#[ObservedBy(AuditObserver::class)]
class SmsCampaign extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'sms_campaigns';

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
        'dlt_template_id',
        'gateway',
        'status',
        'recipient_filter',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'failed_count',
        'scheduled_at',
        'sent_at',
        'created_by',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'gateway'          => SmsGateway::class,
            'status'           => CampaignStatus::class,
            'recipient_filter' => 'array',
            'scheduled_at'     => 'datetime',
            'sent_at'          => 'datetime',
        ];
    }

    public function dltTemplate(): BelongsTo
    {
        return $this->belongsTo(DltTemplate::class, 'dlt_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
