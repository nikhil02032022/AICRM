<?php

declare(strict_types=1);

namespace App\Models\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use App\Enums\CRM\Payments\GatewayProvider;
use App\Enums\CRM\Payments\PaymentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\Lead;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-FM-001, CRM-FM-002, CRM-FM-005 — Payment transaction ledger
class PaymentTransaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'payment_transactions';

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
        'application_uuid', 'lead_uuid', 'fee_structure_id',
        'fee_type', 'gateway',
        'gateway_order_id', 'gateway_payment_id',
        'amount', 'currency', 'status',
        'idempotency_key',
        'attempted_at', 'confirmed_at', 'failure_reason',
        'raw_request', 'raw_response',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fee_type' => FeeType::class,
            'gateway' => GatewayProvider::class,
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'attempted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'raw_request' => 'array',
            'raw_response' => 'array',
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

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(PaymentReminder::class);
    }

    public function refundRequests(): HasMany
    {
        return $this->hasMany(RefundRequest::class);
    }
}
