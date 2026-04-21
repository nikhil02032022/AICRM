<?php

declare(strict_types=1);

namespace App\Models\CRM\Payments;

use App\Enums\CRM\Payments\InstallmentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-FM-009 — Application-specific installment rows
class ApplicationInstallmentSchedule extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'application_installment_schedules';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    protected $fillable = [
        'uuid', 'institution_id', 'application_uuid', 'fee_installment_plan_id',
        'sequence', 'label', 'amount', 'due_date', 'status',
        'payment_transaction_uuid', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'status' => InstallmentStatus::class,
            'paid_at' => 'datetime',
            'sequence' => 'int',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_uuid', 'uuid');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(FeeInstallmentPlan::class, 'fee_installment_plan_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_uuid', 'uuid');
    }
}
