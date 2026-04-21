<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments;

use App\Enums\CRM\Payments\InstallmentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\Payments\ApplicationInstallmentSchedule;
use App\Models\CRM\Payments\FeeInstallmentPlan;
use App\Models\CRM\Payments\PaymentTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// BRD: CRM-FM-009 — apply plans to applications, mark rows paid, recompute.
final class ApplicationInstallmentService
{
    /**
     * Apply a plan to an application, replacing any existing open schedule for the same plan.
     *
     * @return Collection<int, ApplicationInstallmentSchedule>
     */
    public function applyPlan(Application $application, FeeInstallmentPlan $plan): Collection
    {
        return DB::transaction(function () use ($application, $plan): Collection {
            ApplicationInstallmentSchedule::query()
                ->where('application_uuid', $application->uuid)
                ->where('fee_installment_plan_id', $plan->id)
                ->where('status', InstallmentStatus::PENDING->value)
                ->delete();

            $now = now();
            $rows = collect($plan->schedule)->map(function (array $row) use ($application, $plan, $now) {
                $amount = round(((float) $plan->total_amount) * ((float) $row['percent']) / 100.0, 2);

                return ApplicationInstallmentSchedule::create([
                    'institution_id'          => $application->institution_id,
                    'application_uuid'        => $application->uuid,
                    'fee_installment_plan_id' => $plan->id,
                    'sequence'                => (int) $row['sequence'],
                    'label'                   => $row['label'] ?? null,
                    'amount'                  => $amount,
                    'due_date'                => $now->copy()->addDays((int) $row['due_offset_days'])->toDateString(),
                    'status'                  => InstallmentStatus::PENDING->value,
                ]);
            });

            return $rows;
        });
    }

    public function markPaid(ApplicationInstallmentSchedule $schedule, PaymentTransaction $txn): ApplicationInstallmentSchedule
    {
        $schedule->status = InstallmentStatus::PAID;
        $schedule->payment_transaction_uuid = $txn->uuid;
        $schedule->paid_at = now();
        $schedule->save();

        return $schedule;
    }

    /**
     * Mark the earliest open schedule for the given application + fee_type as paid on successful payment.
     */
    public function reconcileFromTransaction(PaymentTransaction $txn): ?ApplicationInstallmentSchedule
    {
        $schedule = ApplicationInstallmentSchedule::query()
            ->where('application_uuid', $txn->application_uuid)
            ->where('status', InstallmentStatus::PENDING->value)
            ->orderBy('sequence')
            ->first();

        if (! $schedule) {
            return null;
        }

        return $this->markPaid($schedule, $txn);
    }

    /**
     * Recompute open schedule amounts after a scholarship is approved (applies pro-rata discount).
     */
    public function recompute(Application $application, float $discountAmount): void
    {
        $openRows = ApplicationInstallmentSchedule::query()
            ->where('application_uuid', $application->uuid)
            ->where('status', InstallmentStatus::PENDING->value)
            ->orderBy('sequence')
            ->get();

        if ($openRows->isEmpty() || $discountAmount <= 0) {
            return;
        }

        $remaining = $discountAmount;
        foreach ($openRows as $row) {
            if ($remaining <= 0) {
                break;
            }
            $cut = (float) min($remaining, (float) $row->amount);
            $row->amount = round(((float) $row->amount) - $cut, 2);
            $row->save();
            $remaining -= $cut;
        }
    }
}
