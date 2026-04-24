<?php

declare(strict_types=1);

namespace App\Models\CRM\Admin;

use App\Enums\CRM\Admin\AcademicYearStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(AuditObserver::class)]
class AcademicYear extends Model
{
    use SoftDeletes;

    protected $table = 'academic_years';

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'label',
        'start_date',
        'end_date',
        'is_active',
        'status',
        'rolled_over_from_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'is_active'   => 'boolean',
            'status'      => AcademicYearStatus::class,
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Institution::class);
    }

    public function rolledOverFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rolled_over_from_id');
    }

    public function rolledOverTo(): HasMany
    {
        return $this->hasMany(self::class, 'rolled_over_from_id');
    }
}
