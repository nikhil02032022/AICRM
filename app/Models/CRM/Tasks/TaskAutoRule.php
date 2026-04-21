<?php

declare(strict_types=1);

namespace App\Models\CRM\Tasks;

use App\Models\CRM\Institution;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\CRM\Task;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-TF-002 — Configurable auto-task rule stored as institution setting
#[ObservedBy(AuditObserver::class)]
final class TaskAutoRule extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'task_auto_rules';

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
        'trigger_type',
        'inactivity_threshold_hours',
        'task_type',
        'priority',
        'assignee_strategy',
        'is_active',
        'metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata'  => 'array',
            'inactivity_threshold_hours' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'auto_rule_id');
    }
}
