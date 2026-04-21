<?php

declare(strict_types=1);

namespace App\Services\CRM\Scholarships;

use App\Enums\CRM\Scholarships\ScholarshipAwardStatus;
use App\Models\CRM\Scholarships\ScholarshipAward;
use Illuminate\Support\Facades\Cache;

// BRD: CRM-FM-012 — scholarship-impact tile on fee dashboard.
final class ScholarshipImpactReporter
{
    /**
     * @return array{total: float, count: int, by_programme: array<int, array{programme_id:?int, total: float, count: int}>}
     */
    public function forInstitution(int $institutionId, ?string $from = null, ?string $to = null): array
    {
        $ttl = (int) config('crm_scholarships.impact.cache_ttl', 300);
        $key = sprintf('scholarship_impact:%d:%s:%s', $institutionId, $from ?? '-', $to ?? '-');

        return Cache::remember($key, $ttl, function () use ($institutionId, $from, $to): array {
            $q = ScholarshipAward::withoutGlobalScopes()
                ->where('scholarship_awards.institution_id', $institutionId)
                ->where('status', ScholarshipAwardStatus::FINANCE_APPROVED->value);

            if ($from) {
                $q->where('finance_approved_at', '>=', $from);
            }
            if ($to) {
                $q->where('finance_approved_at', '<=', $to);
            }

            $rows = (clone $q)
                ->join('scholarship_categories', 'scholarship_categories.id', '=', 'scholarship_awards.scholarship_category_id')
                ->selectRaw('scholarship_categories.programme_id as programme_id, sum(scholarship_awards.amount) as total, count(*) as cnt')
                ->groupBy('scholarship_categories.programme_id')
                ->get();

            $total = (float) $q->sum('amount');
            $count = (int) $q->count();

            return [
                'total' => $total,
                'count' => $count,
                'by_programme' => $rows->map(fn ($r) => [
                    'programme_id' => $r->programme_id ? (int) $r->programme_id : null,
                    'total' => (float) $r->total,
                    'count' => (int) $r->cnt,
                ])->all(),
            ];
        });
    }
}
