<?php

declare(strict_types=1);

namespace App\Models\CRM\Documents;

use App\Enums\CRM\Documents\DocumentCommentType;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-DM-004
class ApplicationDocumentComment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'application_document_comments';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid', 'institution_id', 'application_document_id',
        'actor_id', 'type', 'comment',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentCommentType::class,
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'application_document_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
