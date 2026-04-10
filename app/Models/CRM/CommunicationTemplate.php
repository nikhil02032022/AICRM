<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\TemplateType;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-CC-001 — Reusable communication templates for email, SMS, and WhatsApp
#[ObservedBy(AuditObserver::class)]
class CommunicationTemplate extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'communication_templates';

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
        'name',
        'channel',
        'type',
        'subject',
        'body_html',
        'body_text',
        'merge_tags',
        'is_active',
        'created_by',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'channel'     => CommunicationChannel::class,
            'type'        => TemplateType::class,
            'merge_tags'  => 'array',
            'is_active'   => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
