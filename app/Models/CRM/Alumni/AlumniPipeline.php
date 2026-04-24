<?php

declare(strict_types=1);

namespace App\Models\CRM\Alumni;

use App\Enums\CRM\Alumni\AlumniPipelineStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniPipeline extends Model
{
    protected $table = 'alumni_pipeline';

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'lead_id',
        'application_id',
        'institution_id',
        'programme_id',
        'graduated_at',
        'alumni_status',
    ];

    protected function casts(): array
    {
        return [
            'alumni_status' => AlumniPipelineStatus::class,
            'graduated_at'  => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Lead::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Application::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Institution::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Programme::class);
    }
}
