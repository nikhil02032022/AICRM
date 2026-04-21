<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Your login code</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9fafb; margin: 0; padding: 0; }
        .wrapper { max-width: 520px; margin: 40px auto; background: #ffffff; border-radius: 8px; border: 1px solid #e5e7eb; overflow: hidden; }
        .header { background: #4f46e5; padding: 24px 32px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff; font-size: 20px; font-weight: 600; }
        .body { padding: 32px; }
        .otp-box { background: #f3f4f6; border: 2px dashed #d1d5db; border-radius: 8px; text-align: center; padding: 20px; margin: 24px 0; }
        .otp-code { font-size: 40px; font-weight: 700; letter-spacing: 0.25em; color: #111827; }
        .footer { padding: 16px 32px; border-top: 1px solid #f3f4f6; text-align: center; }
        .footer p { margin: 0; font-size: 12px; color: #9ca3af; }
        p { color: #374151; line-height: 1.6; margin: 0 0 12px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $institutionName }}</h1>
        </div>
        <div class="body">
            <p>You requested a login code for the applicant portal. Use the code below to sign in:</p>

            <div class="otp-box">
                <div class="otp-code">{{ $otp }}</div>
            </div>

            <p>This code is valid for <strong>{{ $expiryMinutes }} minutes</strong> and can only be used once.</p>
            <p>If you did not request this code, you can safely ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $institutionName }}. This is an automated message — please do not reply.</p>
        </div>
    </div>
</body>
</html>
