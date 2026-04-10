<?php

declare(strict_types=1);

namespace App\Services\CRM\Import;

use App\Enums\CRM\IntegrationChannel;
use App\Services\CRM\Import\Normalizers\Careers360LeadNormalizer;
use App\Services\CRM\Import\Normalizers\CollegeDekhoLeadNormalizer;
use App\Services\CRM\Import\Normalizers\CollegeDuniaLeadNormalizer;
use App\Services\CRM\Import\Normalizers\NormalizerContract;
use App\Services\CRM\Import\Normalizers\ShikshaLeadNormalizer;

/**
 * PortalNormalizerService — Strategy dispatcher that resolves the correct
 * portal-specific normalizer from an IntegrationChannel enum value.
 *
 * BRD: CRM-LC-008 — Education portal imports (Shiksha, CollegeDekho, Careers360, Collegedunia)
 */
final class PortalNormalizerService
{
    public function __construct(
        private readonly ShikshaLeadNormalizer $shiksha,
        private readonly CollegeDekhoLeadNormalizer $collegeDekho,
        private readonly Careers360LeadNormalizer $careers360,
        private readonly CollegeDuniaLeadNormalizer $collegedunia,
    ) {}

    /**
     * Resolve the correct normalizer for the given channel.
     *
     * @throws \InvalidArgumentException if the channel is not a known portal
     */
    public function resolve(IntegrationChannel $channel): NormalizerContract
    {
        return match ($channel) {
            IntegrationChannel::SHIKSHA => $this->shiksha,
            IntegrationChannel::COLLEGE_DEKHO => $this->collegeDekho,
            IntegrationChannel::CAREERS360 => $this->careers360,
            IntegrationChannel::COLLEGEDUNIA => $this->collegedunia,
            default => throw new \InvalidArgumentException(
                "No normalizer registered for channel: {$channel->value}"
            ),
        };
    }
}
