<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ReportEntity;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-AR-018 — Institution-managed custom report definition
#[ObservedBy(AuditObserver::class)]
class CustomReport extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'custom_reports';

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
        'created_by',
        'name',
        'description',
        'entity',
        'selected_fields',
        'filters',
        'group_by',
        'sort_field',
        'sort_direction',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'entity'          => ReportEntity::class,
            'selected_fields' => 'array',
            'filters'         => 'array',
            'last_run_at'     => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function exports(): HasMany
    {
        return $this->hasMany(ReportExport::class, 'custom_report_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class, 'custom_report_id');
    }
}
