<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// BRD: CRM-EI-001 — Local cache of Programme catalogue synced from A2A ERP
// Stub: full ERP sync service implemented in Phase 1 Sprint 3
class CrmProgramme extends Model
{
    protected $table = 'crm_programmes';

    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'level',
        'department',
        'is_active',
        'erp_programme_uuid',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope());
    }

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(
            Lead::class,
            'lead_programme_interests',
            'crm_programme_id',
            'lead_id',
        )->withPivot('is_primary')->withTimestamps();
    }
}
