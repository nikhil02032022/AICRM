<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ReportFormat;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-AR-018 — Tracks every export action for DPDP audit trail
class ReportExport extends Model
{
    use HasUuids;

    protected $table = 'report_exports';

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
        'custom_report_id',
        'exported_by',
        'format',
        'storage_path',
        'row_count',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'format'     => ReportFormat::class,
            'row_count'  => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function customReport(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class, 'custom_report_id');
    }

    public function exportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
    }
}
