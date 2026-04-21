<?php

declare(strict_types=1);

namespace App\Models\CRM\Documents;

use App\Enums\CRM\Documents\DocumentStatus;
use App\Enums\CRM\Documents\DocumentUploadChannel;
use App\Models\CRM\Application;
use App\Models\CRM\Lead;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-DM-002, DM-003, DM-004, DM-008
class ApplicationDocument extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'application_documents';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid', 'institution_id', 'campus_id',
        'application_uuid', 'lead_uuid', 'document_checklist_item_id',
        'status', 'storage_disk', 'storage_path',
        'original_filename', 'mime_type', 'size_bytes',
        'uploaded_via', 'uploaded_by', 'uploaded_at',
        'reviewed_by', 'reviewed_at', 'rejection_reason', 'version',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'uploaded_via' => DocumentUploadChannel::class,
            'uploaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'size_bytes'  => 'int',
            'version'     => 'int',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_uuid', 'uuid');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_uuid', 'uuid');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(DocumentChecklistItem::class, 'document_checklist_item_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ApplicationDocumentComment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(DocumentReminder::class);
    }
}
