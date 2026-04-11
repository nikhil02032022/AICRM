<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\WorkflowNodeType;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-MA-001 — Ordered workflow node model for trigger/condition/action steps
#[ObservedBy(AuditObserver::class)]
class WorkflowStep extends Model
{
    use HasUuids, SoftDeletes;

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
        'automation_workflow_id',
        'step_order',
        'node_type',
        'name',
        'config',
        'delay_minutes',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'node_type' => WorkflowNodeType::class,
            'config' => 'array',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(AutomationWorkflow::class, 'automation_workflow_id');
    }
}
