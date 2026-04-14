<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\AlumniBridgeStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-EI-008 — Alumni bridge log — CRM to A2A Alumni Module handoff record
#[ObservedBy(AuditObserver::class)]
class AlumniBridgeLog extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'alumni_bridge_logs';

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
        'erp_student_id',
        'erp_alumni_id',
        'status',
        'referral_code',
        'referrals_count',
        'payload_summary',
        'error_message',
        'bridged_at',
    ];

    protected function casts(): array
    {
        return [
            'status'          => AlumniBridgeStatus::class,
            'payload_summary' => 'array',
            'bridged_at'      => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
