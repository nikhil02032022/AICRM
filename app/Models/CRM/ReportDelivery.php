<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ReportDeliveryStatus;
use App\Enums\CRM\ReportFormat;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AR-020 — Immutable delivery record for each scheduled report dispatch
class ReportDelivery extends Model
{
    use HasUuids;

    protected $table = 'report_deliveries';

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
        'report_schedule_id',
        'custom_report_id',
        'status',
        'recipient_emails',
        'format',
        'sent_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status'           => ReportDeliveryStatus::class,
            'format'           => ReportFormat::class,
            'recipient_emails' => 'array',
            'sent_at'          => 'datetime',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ReportSchedule::class, 'report_schedule_id');
    }

    public function customReport(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class, 'custom_report_id');
    }
}
