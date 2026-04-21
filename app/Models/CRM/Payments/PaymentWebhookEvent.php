<?php

declare(strict_types=1);

namespace App\Models\CRM\Payments;

use App\Enums\CRM\Payments\GatewayProvider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// BRD: CRM-FM-005 — Audit trail for inbound gateway webhooks (idempotency)
class PaymentWebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'payment_webhook_events';

    protected $fillable = [
        'gateway', 'event_id', 'event_type',
        'signature_valid', 'payload',
        'received_at', 'processed_at', 'processing_error',
    ];

    protected function casts(): array
    {
        return [
            'gateway' => GatewayProvider::class,
            'signature_valid' => 'bool',
            'payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
