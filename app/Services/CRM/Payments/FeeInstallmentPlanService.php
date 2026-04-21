<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments;

use App\Models\CRM\Payments\FeeInstallmentPlan;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

// BRD: CRM-FM-009 — CRUD of installment plan templates.
final class FeeInstallmentPlanService
{
    /** @param array<string,mixed> $data */
    public function create(array $data): FeeInstallmentPlan
    {
        $data['institution_id'] ??= Auth::user()?->institution_id;
        $data['created_by']     ??= Auth::id();
        $this->assertSchedule($data['schedule'] ?? []);

        return FeeInstallmentPlan::create($data);
    }

    /** @param array<string,mixed> $data */
    public function update(FeeInstallmentPlan $plan, array $data): FeeInstallmentPlan
    {
        $data['updated_by'] = Auth::id();
        if (array_key_exists('schedule', $data)) {
            $this->assertSchedule($data['schedule']);
        }
        $plan->fill($data)->save();

        return $plan->fresh();
    }

    public function toggle(FeeInstallmentPlan $plan): FeeInstallmentPlan
    {
        $plan->is_active = ! $plan->is_active;
        $plan->updated_by = Auth::id();
        $plan->save();

        return $plan;
    }

    /** @param array<int, array<string,mixed>> $schedule */
    private function assertSchedule(array $schedule): void
    {
        if (count($schedule) < 1) {
            throw new InvalidArgumentException('At least one installment row is required.');
        }
        $sum = 0.0;
        foreach ($schedule as $row) {
            if (! isset($row['sequence'], $row['percent'], $row['due_offset_days'])) {
                throw new InvalidArgumentException('Each installment needs sequence, percent, due_offset_days.');
            }
            $sum += (float) $row['percent'];
        }
        if (abs($sum - 100.0) > 0.01) {
            throw new InvalidArgumentException('Installment percentages must sum to 100 (got '.$sum.').');
        }
    }
}
