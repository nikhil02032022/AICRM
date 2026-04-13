<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-MA-003 — CRM tag entity for lead tagging action automation
#[ObservedBy(AuditObserver::class)]
class Tag extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'crm_tags';

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
        'name',
        'color',
    ];

    public function leads(): BelongsToMany
    {
        return $this->belongsToMany(Lead::class, 'lead_tag', 'crm_tag_id', 'lead_id')
            ->withPivot('institution_id')
            ->withTimestamps();
    }
}
