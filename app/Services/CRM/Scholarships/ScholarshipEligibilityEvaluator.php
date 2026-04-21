<?php

declare(strict_types=1);

namespace App\Services\CRM\Scholarships;

use App\Models\CRM\Application;
use App\Models\CRM\Scholarships\ScholarshipCategory;
use App\Models\CRM\Scholarships\ScholarshipEligibilityRule;
use Illuminate\Support\Collection;

// BRD: CRM-FM-007 — Auto-evaluate applicants against scholarship rules.
final class ScholarshipEligibilityEvaluator
{
    private const OPERATORS = ['gte', 'lte', 'eq', 'in', 'between'];

    /**
     * @return Collection<int, ScholarshipCategory>
     */
    public function evaluate(Application $application): Collection
    {
        $whitelist = (array) config('crm_scholarships.eligibility_attributes', []);

        $categories = ScholarshipCategory::query()
            ->where('is_active', true)
            ->where(function ($q) use ($application): void {
                $q->whereNull('programme_id')->orWhere('programme_id', $application->programme_id);
            })
            ->with('eligibilityRules')
            ->get();

        return $categories->filter(function (ScholarshipCategory $cat) use ($application, $whitelist): bool {
            $rules = $cat->eligibilityRules;
            if ($rules->isEmpty()) {
                return true; // no rules == always eligible
            }

            return $this->combine($rules, $application, $whitelist);
        })->values();
    }

    /**
     * Evaluate the rule tree. Combinator on rule N ties rule N-1 with rule N.
     * @param iterable<ScholarshipEligibilityRule> $rules
     * @param array<int,string> $whitelist
     */
    private function combine(iterable $rules, Application $application, array $whitelist): bool
    {
        $result = null;
        foreach ($rules as $rule) {
            if (! in_array($rule->attribute, $whitelist, true)) {
                continue;
            }
            if (! in_array($rule->operator, self::OPERATORS, true)) {
                continue;
            }
            $hit = $this->matches($rule, $application);
            if ($result === null) {
                $result = $hit;
                continue;
            }
            $result = $rule->combinator === 'OR' ? ($result || $hit) : ($result && $hit);
        }

        return (bool) $result;
    }

    private function matches(ScholarshipEligibilityRule $rule, Application $application): bool
    {
        $attrValue = $this->resolve($rule->attribute, $application);
        $expected  = $rule->value;

        return match ($rule->operator) {
            'gte'     => is_numeric($attrValue) && (float) $attrValue >= (float) $this->scalar($expected),
            'lte'     => is_numeric($attrValue) && (float) $attrValue <= (float) $this->scalar($expected),
            'eq'      => (string) $attrValue === (string) $this->scalar($expected),
            'in'      => is_array($expected) && in_array($attrValue, $expected, false),
            'between' => is_array($expected) && count($expected) === 2
                         && is_numeric($attrValue)
                         && (float) $attrValue >= (float) $expected[0]
                         && (float) $attrValue <= (float) $expected[1],
            default   => false,
        };
    }

    /** @param mixed $value */
    private function scalar($value): string|int|float|null
    {
        if (is_array($value)) {
            return isset($value[0]) && (is_scalar($value[0]) || $value[0] === null) ? $value[0] : null;
        }

        return is_scalar($value) ? $value : null;
    }

    private function resolve(string $path, Application $application): mixed
    {
        [$root, $field] = array_pad(explode('.', $path, 2), 2, null);
        $entity = match ($root) {
            'application' => $application,
            'lead' => $application->lead,
            default => null,
        };

        return $entity?->{$field};
    }
}
