<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\DiallerLogStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-TC-001 — Dialler queue row linked to a lead and optional call log
#[ObservedBy(AuditObserver::class)]
class DiallerLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'dialler_logs';

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
        'dialler_session_id',
        'lead_id',
        'call_log_id',
        'queue_order',
        'status',
        'failure_reason',
        'attempted_at',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DiallerLogStatus::class,
            'queue_order' => 'integer',
            'attempted_at' => 'datetime',
            'placed_at' => 'datetime',
        ];
    }

    public function diallerSession(): BelongsTo
    {
        return $this->belongsTo(DiallerSession::class, 'dialler_session_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function callLog(): BelongsTo
    {
        return $this->belongsTo(CallLog::class, 'call_log_id');
    }
}
