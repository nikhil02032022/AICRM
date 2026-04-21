<?php

declare(strict_types=1);

namespace App\Models\CRM\Documents;

use App\Enums\CRM\Documents\BulkDownloadStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-DM-009
class DocumentBulkDownloadJob extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'document_bulk_download_jobs';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid', 'institution_id', 'requested_by',
        'scope', 'target_ref', 'status',
        'file_count', 'zip_path', 'zip_size_bytes',
        'failure_reason', 'expires_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BulkDownloadStatus::class,
            'file_count' => 'int',
            'zip_size_bytes' => 'int',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
