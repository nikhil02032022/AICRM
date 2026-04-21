<?php

declare(strict_types=1);

namespace App\Models\CRM\Documents;

use App\Enums\CRM\Documents\DocumentReminderStatus;
use App\Enums\CRM\Payments\PaymentChannel;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-DM-005
class DocumentReminder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'document_reminders';

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
        'scheduled_for', 'channel', 'status', 'opted_out',
        'sent_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'channel'       => PaymentChannel::class,
            'status'        => DocumentReminderStatus::class,
            'scheduled_for' => 'datetime',
            'sent_at'       => 'datetime',
            'opted_out'     => 'bool',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ApplicationDocument::class, 'application_document_id');
    }
}
