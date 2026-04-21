<?php

declare(strict_types=1);

namespace App\Models\CRM\Tasks;

use App\Models\CRM\Institution;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role;

// BRD: CRM-TF-004 — Overdue task escalation rules per institution
#[ObservedBy(AuditObserver::class)]
final class TaskEscalationRule extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'task_escalation_rules';

    /** @return list<string> */
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
        'overdue_threshold_hours',
        'escalate_to_role_id',
        'notification_channel',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active'               => 'boolean',
            'overdue_threshold_hours' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'escalate_to_role_id');
    }
}
