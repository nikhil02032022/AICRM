<?php

declare(strict_types=1);

namespace App\Support;

// BRD: CRM-AI-007, DPDP — Regex-based PII scrubbing for transcript text before AI usage logging.
// Applied to the logged payload only; the original transcript text passed to Claude is unchanged.
final class TranscriptRedactor
{
    public static function redact(string $text): string
    {
        // Email addresses
        $text = preg_replace(
            '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
            '[EMAIL REDACTED]',
            $text,
        ) ?? $text;

        // Indian 10-digit mobile numbers (with optional country code +91 or 0)
        $text = preg_replace(
            '/(?:\+91|0)?[6-9]\d{9}/',
            '[PHONE REDACTED]',
            $text,
        ) ?? $text;

        // Aadhaar-like 12-digit numbers (spaced or plain)
        $text = preg_replace(
            '/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/',
            '[AADHAAR REDACTED]',
            $text,
        ) ?? $text;

        return $text;
    }
}
