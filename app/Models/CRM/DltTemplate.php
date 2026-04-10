<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\DltMessageType;
use App\Enums\CRM\DltTemplateStatus;
use App\Enums\CRM\SmsGateway;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-CC-008 — DLT template registration workflow (TRAI compliance for India SMS)
#[ObservedBy(AuditObserver::class)]
class DltTemplate extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'dlt_templates';

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
        'sender_id',
        'template_name',
        'dlt_template_id',
        'template_body',
        'message_type',
        'gateway',
        'status',
        'approval_notes',
        'submitted_at',
        'approved_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'message_type'  => DltMessageType::class,
            'gateway'       => SmsGateway::class,
            'status'        => DltTemplateStatus::class,
            'submitted_at'  => 'datetime',
            'approved_at'   => 'datetime',
        ];
    }

    public function canSend(): bool
    {
        return $this->status === DltTemplateStatus::APPROVED;
    }
}
