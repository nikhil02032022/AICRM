<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AI-008 — Enrolment forecast snapshot per programme and month for planning
class EnrolmentForecast extends Model
{
    use HasUuids;

    protected $table = 'enrolment_forecasts';

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
        'crm_programme_id',
        'admission_cycle',
        'forecast_count',
        'confidence_score',
        'inputs',
        'model_version',
        'generated_for_month',
        'generated_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'forecast_count' => 'integer',
            'confidence_score' => 'integer',
            'inputs' => 'array',
            'generated_for_month' => 'date',
            'generated_at' => 'datetime',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(CrmProgramme::class, 'crm_programme_id');
    }
}
