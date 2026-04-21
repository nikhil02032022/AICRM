<?php

declare(strict_types=1);

namespace App\Models\CRM\Payments;

use App\Enums\CRM\Payments\PaymentChannel;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// BRD: CRM-FM-004 — Shareable payment link
class PaymentLink extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'payment_links';

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
        'token', 'channel', 'recipient',
        'shared_at', 'expires_at', 'opened_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'channel' => PaymentChannel::class,
            'shared_at' => 'datetime',
            'expires_at' => 'datetime',
            'opened_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }
}
