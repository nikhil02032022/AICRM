<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
<body style="font-family: Inter, Arial, sans-serif; background: #f9fafb; margin: 0; padding: 32px 16px;">
    <div style="max-width: 520px; margin: 0 auto; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 32px;">

        <h2 style="margin: 0 0 8px; font-size: 18px; font-weight: 700; color: #111827;">
            {{ $failed > 0 ? '⚠️ Import completed with errors' : '✅ Import completed successfully' }}
        </h2>

        <p style="margin: 0 0 24px; font-size: 14px; color: #6b7280;">
            Your bulk lead import for <strong>{{ $batch->file_name ?? 'batch ' . substr($batch->uuid, 0, 8) }}</strong> has finished.
        </p>

        <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 24px;">
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 10px 0; color: #374151; font-weight: 500;">Total rows</td>
                <td style="padding: 10px 0; text-align: right; color: #111827; font-weight: 700;">{{ number_format($batch->total_rows) }}</td>
            </tr>
            <tr style="border-bottom: 1px solid #e5e7eb;">
                <td style="padding: 10px 0; color: #374151; font-weight: 500;">Successfully imported</td>
                <td style="padding: 10px 0; text-align: right; color: #16a34a; font-weight: 700;">{{ number_format($successful) }}</td>
            </tr>
            @if($failed > 0)
            <tr>
                <td style="padding: 10px 0; color: #374151; font-weight: 500;">Failed rows</td>
                <td style="padding: 10px 0; text-align: right; color: #dc2626; font-weight: 700;">{{ number_format($failed) }}</td>
            </tr>
            @endif
        </table>

        @if($errorReportUrl)
        <a href="{{ $errorReportUrl }}"
           style="display: inline-block; background: #4f46e5; color: #fff; font-size: 14px; font-weight: 600; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-bottom: 24px;">
            Download Error Report
        </a>
        <p style="margin: 0 0 24px; font-size: 12px; color: #9ca3af;">This download link expires in 24 hours.</p>
        @endif

        <p style="margin: 0; font-size: 12px; color: #9ca3af;">
            A2A CRM — DPDP compliant · Do not reply to this email.
        </p>
    </div>
</body>
</html>
