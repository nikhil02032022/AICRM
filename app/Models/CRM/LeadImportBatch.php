<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ImportBatchStatus;
use App\Enums\CRM\IntegrationChannel;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-LC-012 — Tracks bulk CSV/Excel import batch lifecycle with progress + error report
// BRD: CRM-LC-008 — Also used to record portal CSV batch imports
#[ObservedBy(AuditObserver::class)]
class LeadImportBatch extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'lead_import_batches';

    /**
     * HasUuids targets only the 'uuid' column.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // BRD: NFR-MT-001 — InstitutionScope enforces multi-tenant isolation
    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'channel',
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'error_report_path',
        'initiated_by_user_id',
        'job_batch_id',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'channel' => IntegrationChannel::class,
            'status' => ImportBatchStatus::class,
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'failed_rows' => 'integer',
        ];
    }

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    public function isComplete(): bool
    {
        return $this->status === ImportBatchStatus::COMPLETED;
    }

    public function hasFailed(): bool
    {
        return $this->status === ImportBatchStatus::FAILED;
    }

    /** Percentage of rows successfully processed. */
    public function successRate(): float
    {
        if ($this->total_rows === 0) {
            return 0.0;
        }

        $successful = $this->processed_rows - $this->failed_rows;

        return round(($successful / $this->total_rows) * 100, 1);
    }
}
