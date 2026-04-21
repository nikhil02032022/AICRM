<?php

declare(strict_types=1);

namespace App\Services\CRM\Scholarships;

use App\Models\CRM\Scholarships\ScholarshipCategory;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

// BRD: CRM-FM-006 — CRUD of scholarship/waiver categories.
final class ScholarshipCategoryService
{
    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): ScholarshipCategory
    {
        $data['institution_id'] ??= Auth::user()?->institution_id;
        $data['created_by']     ??= Auth::id();
        $this->assertDates($data);
        $this->assertComputation($data);

        return ScholarshipCategory::create($data);
    }

    /**
     * @param array<string,mixed> $data
     */
    public function update(ScholarshipCategory $category, array $data): ScholarshipCategory
    {
        $data['updated_by'] = Auth::id();
        $this->assertDates($data + $category->only(['effective_from', 'effective_to']));
        $this->assertComputation($data + $category->only(['computation', 'value']));
        $category->fill($data)->save();

        return $category->fresh();
    }

    public function toggle(ScholarshipCategory $category): ScholarshipCategory
    {
        $category->is_active = ! $category->is_active;
        $category->updated_by = Auth::id();
        $category->save();

        return $category;
    }

    /** @param array<string,mixed> $data */
    private function assertDates(array $data): void
    {
        $from = $data['effective_from'] ?? null;
        $to   = $data['effective_to'] ?? null;
        if ($from && $to && strtotime((string) $from) > strtotime((string) $to)) {
            throw new InvalidArgumentException('effective_from must be before effective_to');
        }
    }

    /** @param array<string,mixed> $data */
    private function assertComputation(array $data): void
    {
        $comp = $data['computation'] ?? null;
        if ($comp && ! in_array($comp, ['percent', 'flat'], true)) {
            throw new InvalidArgumentException('computation must be percent or flat');
        }
        if ($comp === 'percent' && isset($data['value']) && ((float) $data['value'] < 0 || (float) $data['value'] > 100)) {
            throw new InvalidArgumentException('percent value must be 0..100');
        }
    }
}
