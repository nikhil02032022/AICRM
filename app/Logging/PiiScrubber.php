<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * PiiScrubber — Monolog processor that redacts PII from all log messages.
 *
 * BRD: No PII in logs (DPDP Act 2023).
 * Registered in config/logging.php as a processor on the stack channel.
 *
 * Patterns redacted:
 *   - Indian mobile numbers (10-digit starting with 6-9)
 *   - Email addresses
 *   - Aadhaar numbers (12-digit)
 *   - PAN card numbers
 */
final class PiiScrubber implements ProcessorInterface
{
    /**
     * @var array<string, string>
     */
    private const PATTERNS = [
        '/\b[6-9]\d{9}\b/' => '[MOBILE_REDACTED]',
        '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/' => '[EMAIL_REDACTED]',
        '/\b\d{4}\s?\d{4}\s?\d{4}\b/' => '[AADHAAR_REDACTED]',
        '/\b[A-Z]{5}[0-9]{4}[A-Z]\b/' => '[PAN_REDACTED]',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        $message = $record->message;

        foreach (self::PATTERNS as $pattern => $replacement) {
            $message = preg_replace($pattern, $replacement, $message) ?? $message;
        }

        return $record->with(message: $message);
    }
}
