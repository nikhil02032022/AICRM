<?php

declare(strict_types=1);

namespace App\Models\CRM\Alumni;

use App\Enums\CRM\Alumni\NpsSnapshotSource;
use App\Models\CRM\Admin\AcademicYear;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AL-004 — NPS score snapshot per institution per academic year (optional: per programme)
class AlumniNpsSnapshot extends Model
{
    protected $table = 'alumni_nps_snapshots';

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'institution_id',
        'academic_year_id',
        'programme_id',
        'nps_score',
        'promoters_pct',
        'neutrals_pct',
        'detractors_pct',
        'survey_date',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'source'          => NpsSnapshotSource::class,
            'survey_date'     => 'date',
            'nps_score'       => 'integer',
            'promoters_pct'   => 'decimal:2',
            'neutrals_pct'    => 'decimal:2',
            'detractors_pct'  => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\CrmProgramme::class, 'programme_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Institution::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    // BRD: CRM-AL-004 — NPS colour coding: green > 50, amber 0-50, red < 0
    public function scoreColourClass(): string
    {
        return match (true) {
            $this->nps_score > 50  => 'text-green-600',
            $this->nps_score >= 0  => 'text-yellow-500',
            default                => 'text-red-500',
        };
    }

    public function scoreLabel(): string
    {
        return ($this->nps_score >= 0 ? '+' : '') . $this->nps_score;
    }
}
