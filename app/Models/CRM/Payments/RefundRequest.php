<?php

declare(strict_types=1);

namespace App\Models\CRM\Payments;

use App\Enums\CRM\Payments\RefundStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-FM-011 — Refund request workflow record
class RefundRequest extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'refund_requests';

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
        'requested_by', 'reason', 'amount',
        'status', 'approver_chain',
        'gateway_refund_id', 'processed_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'amount' => 'decimal:2',
            'approver_chain' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
