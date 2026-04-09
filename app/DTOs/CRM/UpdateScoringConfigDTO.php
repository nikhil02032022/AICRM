<?php

declare(strict_types=1);

namespace App\DTOs\CRM;

// BRD: CRM-LQ-001, CRM-LQ-005 — Data for updating an institution's scoring configuration
final readonly class UpdateScoringConfigDTO
{
    /**
     * @param array<string, int> $weights
     */
    public function __construct(
        public array $weights,
        public int   $hotThreshold,
        public int   $warmThreshold,
    ) {}

    /**
     * Build from validated request array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            weights: [
                'profile_completeness' => (int) $data['profile_completeness'],
                'programme_interest'   => (int) $data['programme_interest'],
                'source_quality'       => (int) $data['source_quality'],
                'engagement'           => (int) $data['engagement'],
                'consent'              => (int) $data['consent'],
                'geographic'           => (int) $data['geographic'],
                'response_time'        => (int) $data['response_time'],
            ],
            hotThreshold:  (int) $data['hot_threshold'],
            warmThreshold: (int) $data['warm_threshold'],
        );
    }
}
