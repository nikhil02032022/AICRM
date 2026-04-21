<?php

declare(strict_types=1);

namespace App\Models\CRM\Payments;

use App\Enums\CRM\Payments\PaymentChannel;
use App\Enums\CRM\Payments\ReminderStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-FM-010 — Scheduled reminder rows for a payment transaction
class PaymentReminder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payment_reminders';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid', 'institution_id', 'payment_transaction_id',
        'due_at', 'scheduled_for', 'channel', 'status',
        'opted_out', 'sent_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'channel' => PaymentChannel::class,
            'status' => ReminderStatus::class,
            'due_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'opted_out' => 'bool',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}
