<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\WorkflowTemplateCategory;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-SA-007 — Pre-built and institution-specific workflow automation templates
#[ObservedBy(AuditObserver::class)]
class WorkflowTemplate extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'workflow_templates';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        // Global templates (institution_id = null) are visible to all; scoping is handled
        // in the service/repository via withoutGlobalScope when needed.
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'name',
        'description',
        'category',
        'trigger_type',
        'template_data',
        'is_global',
        'is_active',
        'sort_order',
        'used_count',
    ];

    protected function casts(): array
    {
        return [
            'category'      => WorkflowTemplateCategory::class,
            'template_data' => 'array',
            'is_global'     => 'boolean',
            'is_active'     => 'boolean',
            'sort_order'    => 'integer',
            'used_count'    => 'integer',
        ];
    }
}
