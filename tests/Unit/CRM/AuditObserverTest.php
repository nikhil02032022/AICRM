<?php

declare(strict_types=1);

use App\Logging\PiiScrubber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Monolog\Level;
use Monolog\LogRecord;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// PiiScrubber Tests
// ---------------------------------------------------------------------------

test('pii scrubber redacts indian mobile numbers from log messages', function (): void {
    $scrubber = new PiiScrubber;
    $record = makeLogRecord('Lead created for mobile 9876543210 in admission cycle');

    $result = $scrubber($record);

    expect($result->message)
        ->not->toContain('9876543210')
        ->toContain('[MOBILE_REDACTED]');
});

test('pii scrubber redacts email addresses from log messages', function (): void {
    $scrubber = new PiiScrubber;
    $record = makeLogRecord('New enquiry from student@example.com submitted');

    $result = $scrubber($record);

    expect($result->message)
        ->not->toContain('student@example.com')
        ->toContain('[EMAIL_REDACTED]');
});

test('pii scrubber redacts aadhaar numbers from log messages', function (): void {
    $scrubber = new PiiScrubber;
    $record = makeLogRecord('Document uploaded with Aadhaar 1234 5678 9012');

    $result = $scrubber($record);

    expect($result->message)
        ->not->toContain('1234 5678 9012')
        ->toContain('[AADHAAR_REDACTED]');
});

test('pii scrubber redacts PAN numbers from log messages', function (): void {
    $scrubber = new PiiScrubber;
    $record = makeLogRecord('PAN verification for ABCDE1234F initiated');

    $result = $scrubber($record);

    expect($result->message)
        ->not->toContain('ABCDE1234F')
        ->toContain('[PAN_REDACTED]');
});

test('pii scrubber does not alter messages without pii', function (): void {
    $scrubber = new PiiScrubber;
    $message = 'Lead status changed from New to Contacted by counsellor ID 42';
    $record = makeLogRecord($message);

    $result = $scrubber($record);

    expect($result->message)->toBe($message);
});

// ---------------------------------------------------------------------------
// AuditObserver Tests
// ---------------------------------------------------------------------------

test('audit log table exists and is empty initially', function (): void {
    expect(DB::table('audit_logs')->count())->toBe(0);
});

test('consent records table exists and is empty initially', function (): void {
    expect(DB::table('consent_records')->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function makeLogRecord(string $message): LogRecord
{
    return new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'crm',
        level: Level::Info,
        message: $message,
        context: [],
        extra: [],
    );
}
