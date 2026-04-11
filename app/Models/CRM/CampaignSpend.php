<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\AttributionModel;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-LC-017 — Stores campaign/source spend entries used for CPL reporting.
class CampaignSpend extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'campaign_spends';

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
        'source',
        'campaign_name',
        'period_start',
        'period_end',
        'amount',
        'currency',
        'attribution_model',
        'notes',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'attribution_model' => AttributionModel::class,
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
