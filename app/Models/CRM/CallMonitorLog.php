<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\CallMonitorMode;
use App\Enums\CRM\CallMonitorStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-TC-005 — Supervisor call monitoring sessions
#[ObservedBy(AuditObserver::class)]
class CallMonitorLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'call_monitor_logs';

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
        'call_log_id',
        'supervisor_id',
        'mode',
        'status',
        'provider_session_id',
        'consent_validated',
        'started_at',
        'ended_at',
        'duration_seconds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'mode' => CallMonitorMode::class,
            'status' => CallMonitorStatus::class,
            'consent_validated' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'call_log_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
