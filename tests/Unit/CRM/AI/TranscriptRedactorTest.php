<?php

declare(strict_types=1);

// BRD: CRM-AI-007, DPDP — Unit tests for TranscriptRedactor PII scrubbing utility

use App\Support\TranscriptRedactor;

it('redacts email addresses', function (): void {
    $text = 'Please contact me at student@example.com for more details.';

    expect(TranscriptRedactor::redact($text))
        ->toContain('[EMAIL REDACTED]')
        ->not->toContain('student@example.com');
});

it('redacts multiple email addresses in one string', function (): void {
    $text = 'Email admin@college.edu or support@test.org for help.';

    $redacted = TranscriptRedactor::redact($text);

    expect($redacted)->not->toContain('@college.edu')
        ->and($redacted)->not->toContain('@test.org');
});

it('redacts 10-digit Indian mobile numbers', function (): void {
    $text = 'Call me on 9876543210 after 5 pm.';

    expect(TranscriptRedactor::redact($text))
        ->toContain('[PHONE REDACTED]')
        ->not->toContain('9876543210');
});

it('redacts mobile numbers with +91 country code', function (): void {
    $text = 'My number is +919876543210.';

    expect(TranscriptRedactor::redact($text))
        ->toContain('[PHONE REDACTED]')
        ->not->toContain('9876543210');
});

it('redacts Aadhaar-pattern 12-digit numbers', function (): void {
    $text = 'Aadhaar number is 1234 5678 9012.';

    expect(TranscriptRedactor::redact($text))
        ->toContain('[AADHAAR REDACTED]')
        ->not->toContain('1234 5678 9012');
});

it('redacts plain 12-digit Aadhaar numbers', function (): void {
    $text = 'ID: 123456789012 was verified.';

    expect(TranscriptRedactor::redact($text))
        ->toContain('[AADHAAR REDACTED]')
        ->not->toContain('123456789012');
});

it('leaves non-PII text unchanged', function (): void {
    $text = 'Student is interested in the MBA programme starting in July 2026.';

    expect(TranscriptRedactor::redact($text))->toBe($text);
});

it('handles empty string without error', function (): void {
    expect(TranscriptRedactor::redact(''))->toBe('');
});
