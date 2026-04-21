<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\Tasks\TaskDisposition;
use App\Enums\CRM\Tasks\TaskPriority;
use App\Enums\CRM\Tasks\TaskSource;
use App\Enums\CRM\Tasks\TaskStatus;
use App\Enums\CRM\Tasks\TaskType;
use App\Models\CRM\Institution;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\CRM\Tasks\TaskAutoRule;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-TF-001 to CRM-TF-009 — Core task entity for counsellor follow-up management
#[ObservedBy(AuditObserver::class)]
class Task extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'crm_tasks';

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
        'lead_id',
        'assigned_to',
        'created_by',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'disposition',
        'source',
        'auto_rule_id',
        'due_at',
        'completed_at',
        'overdue_flagged_at',
        'metadata',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'type'               => TaskType::class,
            'priority'           => TaskPriority::class,
            'status'             => TaskStatus::class,
            'disposition'        => TaskDisposition::class,
            'source'             => TaskSource::class,
            'due_at'             => 'datetime',
            'completed_at'       => 'datetime',
            'overdue_flagged_at' => 'datetime',
            'metadata'           => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function autoRule(): BelongsTo
    {
        return $this->belongsTo(TaskAutoRule::class, 'auto_rule_id');
    }

    // ── Local Scopes ───────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [TaskStatus::Pending->value, TaskStatus::InProgress->value]);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('status', TaskStatus::Overdue->value)
              ->orWhere(function (Builder $inner): void {
                  $inner->where('due_at', '<', now())
                        ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value]);
              });
        });
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->whereBetween('due_at', [today()->startOfDay(), today()->endOfDay()]);
    }

    public function scopeForCounsellor(Builder $query, User $user): Builder
    {
        return $query->where('assigned_to', $user->id);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && ! $this->status->isTerminal();
    }
}
