<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments;

use App\Enums\CRM\Payments\FeeType;
use App\Models\CRM\Payments\FeeStructure;
use Illuminate\Support\Facades\Auth;

// BRD: CRM-FM-001, CRM-FM-002 — CRUD for programme-wise fee structures.
final class FeeStructureService
{
    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): FeeStructure
    {
        $data['institution_id'] ??= Auth::user()?->institution_id;
        $data['created_by']     ??= Auth::id();
        $data['currency']       ??= config('crm_payments.default_currency');

        return FeeStructure::create($data);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function update(FeeStructure $fs, array $data): FeeStructure
    {
        $data['updated_by'] = Auth::id();
        $fs->fill($data)->save();

        return $fs->fresh();
    }

    public function toggle(FeeStructure $fs): FeeStructure
    {
        $fs->is_active = ! $fs->is_active;
        $fs->updated_by = Auth::id();
        $fs->save();

        return $fs;
    }

    public function resolveActive(int $programmeId, FeeType $feeType): ?FeeStructure
    {
        return FeeStructure::query()
            ->where('programme_id', $programmeId)
            ->where('fee_type', $feeType->value)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();
    }
}
