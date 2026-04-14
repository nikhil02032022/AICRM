<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ReportFormat;
use App\Enums\CRM\ReportFrequency;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-AR-020 — Scheduled report delivery configuration
#[ObservedBy(AuditObserver::class)]
class ReportSchedule extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'report_schedules';

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
        'custom_report_id',
        'created_by',
        'name',
        'frequency',
        'day_of_week',
        'day_of_month',
        'run_time',
        'recipient_emails',
        'format',
        'is_active',
        'last_sent_at',
        'next_run_at',
    ];

    protected function casts(): array
    {
        return [
            'frequency'        => ReportFrequency::class,
            'format'           => ReportFormat::class,
            'recipient_emails' => 'array',
            'is_active'        => 'boolean',
            'day_of_week'      => 'integer',
            'day_of_month'     => 'integer',
            'last_sent_at'     => 'datetime',
            'next_run_at'      => 'datetime',
        ];
    }

    public function customReport(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class, 'custom_report_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ReportDelivery::class, 'report_schedule_id');
    }
}
