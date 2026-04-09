<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔥 HOT Lead Alert</title>
    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #F3F4F6; color: #111827; -webkit-font-smoothing: antialiased; }
        a { color: inherit; text-decoration: none; }
        /* Wrapper */
        .wrapper { max-width: 580px; margin: 32px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        /* Header */
        .header { background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); padding: 36px 36px 28px; text-align: center; }
        .header-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.15); border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 14px; font-size: 28px; line-height: 1; }
        .header h1 { font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -0.3px; }
        .header p { margin-top: 6px; font-size: 13px; color: rgba(255,255,255,0.82); }
        /* Score badge */
        .score-band { background: #FFF7F7; border-bottom: 1px solid #FEE2E2; padding: 20px 36px; display: flex; align-items: center; gap: 20px; }
        .score-ring { width: 64px; height: 64px; flex-shrink: 0; border-radius: 50%; background: conic-gradient(#EF4444 {{ round(($score / 100) * 360) }}deg, #FCA5A5 0deg); display: flex; align-items: center; justify-content: center; position: relative; }
        .score-ring::after { content: ''; position: absolute; width: 46px; height: 46px; border-radius: 50%; background: #FFF7F7; }
        .score-number { position: relative; z-index: 1; font-size: 18px; font-weight: 800; color: #DC2626; }
        .score-meta h2 { font-size: 17px; font-weight: 700; color: #111827; }
        .score-meta p { font-size: 12px; color: #6B7280; margin-top: 3px; }
        .badge-hot { display: inline-flex; align-items: center; gap: 4px; background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; border-radius: 999px; padding: 2px 10px; font-size: 11px; font-weight: 700; letter-spacing: 0.3px; text-transform: uppercase; }
        /* Body */
        .body { padding: 28px 36px; }
        .greeting { font-size: 14px; color: #374151; line-height: 1.6; margin-bottom: 20px; }
        .greeting strong { color: #111827; }
        /* Info card */
        .info-card { border: 1px solid #E5E7EB; border-radius: 10px; overflow: hidden; margin-bottom: 24px; }
        .info-card-header { background: #F9FAFB; padding: 10px 16px; font-size: 10px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #6B7280; border-bottom: 1px solid #E5E7EB; }
        .info-row { padding: 11px 16px; border-bottom: 1px solid #F3F4F6; display: flex; justify-content: space-between; align-items: center; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-size: 12px; color: #6B7280; font-weight: 500; }
        .info-value { font-size: 12px; color: #111827; font-weight: 600; text-align: right; max-width: 70%; word-break: break-word; }
        /* Score bar */
        .score-bar-wrap { margin: 4px 16px 12px; }
        .score-bar-bg { background: #FEE2E2; border-radius: 999px; height: 6px; margin-top: 6px; }
        .score-bar-fill { background: #EF4444; height: 6px; border-radius: 999px; width: {{ $score }}%; }
        /* CTA */
        .cta-wrap { text-align: center; margin-bottom: 24px; }
        .cta-btn { display: inline-block; background: linear-gradient(135deg, #EF4444, #DC2626); color: #fff !important; font-size: 14px; font-weight: 700; padding: 13px 32px; border-radius: 8px; letter-spacing: 0.2px; }
        /* Note */
        .note { background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 8px; padding: 12px 14px; margin-bottom: 24px; }
        .note p { font-size: 12px; color: #92400E; line-height: 1.5; }
        /* Footer */
        .footer { border-top: 1px solid #F3F4F6; padding: 20px 36px; text-align: center; }
        .footer p { font-size: 11px; color: #9CA3AF; line-height: 1.6; }
        .footer a { color: #6366F1; }
    </style>
</head>
<body>
    <div class="wrapper">

        {{-- Header --}}
        <div class="header">
            <div class="header-icon">🔥</div>
            <h1>HOT Lead Alert</h1>
            <p>A lead in your pipeline has reached HOT temperature</p>
        </div>

        {{-- Score band --}}
        <div class="score-band">
            <div style="flex-shrink:0;width:64px;height:64px;position:relative">
                <svg width="64" height="64" viewBox="0 0 64 64">
                    <circle cx="32" cy="32" r="26" fill="none" stroke="#FEE2E2" stroke-width="7" transform="rotate(-90 32 32)"/>
                    @php $arc = round(($score / 100) * (2 * M_PI * 26), 2); $circ = round(2 * M_PI * 26, 2); @endphp
                    <circle cx="32" cy="32" r="26" fill="none" stroke="#EF4444" stroke-width="7"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $arc }} {{ $circ }}"
                            transform="rotate(-90 32 32)"/>
                </svg>
                <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center">
                    <span style="font-size:14px;font-weight:800;color:#DC2626">{{ $score }}</span>
                </div>
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <span style="font-size:15px;font-weight:700;color:#111827">{{ $lead->fullName() }}</span>
                    <span class="badge-hot">🔥 HOT</span>
                </div>
                <p style="font-size:12px;color:#6B7280">Lead Score: <strong style="color:#DC2626">{{ $score }}/100</strong> &middot; Source: {{ $source }}</p>
            </div>
        </div>

        {{-- Body --}}
        <div class="body">
            <p class="greeting">
                Hi <strong>{{ $counsellor->name ?? 'Counsellor' }}</strong>,<br><br>
                A lead assigned to you has crossed the <strong>HOT threshold</strong> and is showing strong conversion signals.
                Review their profile now and initiate contact while engagement is at its peak.
            </p>

            {{-- Lead info card --}}
            <div class="info-card">
                <div class="info-card-header">Lead Details</div>
                <div class="info-row">
                    <span class="info-label">Name</span>
                    <span class="info-value">{{ $lead->fullName() }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Lead Score</span>
                    <span class="info-value" style="color:#DC2626;font-weight:800">{{ $score }} / 100</span>
                </div>
                <div class="score-bar-wrap">
                    <div class="score-bar-bg">
                        <div class="score-bar-fill"></div>
                    </div>
                </div>
                <div class="info-row">
                    <span class="info-label">Temperature</span>
                    <span class="info-value">🔥 {{ $temperature }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Source</span>
                    <span class="info-value">{{ $source }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Programme Interest</span>
                    <span class="info-value">{{ $programme }}</span>
                </div>
                @if($lead->city || $lead->state)
                <div class="info-row">
                    <span class="info-label">Location</span>
                    <span class="info-value">{{ collect([$lead->city, $lead->state])->filter()->implode(', ') }}</span>
                </div>
                @endif
            </div>

            {{-- Note --}}
            <div class="note">
                <p>⏱ <strong>Act quickly.</strong> HOT leads have a significantly higher conversion rate when contacted within 1 hour of reaching HOT status. Check their full profile for the best next action.</p>
            </div>

            {{-- CTA button --}}
            <div class="cta-wrap">
                <a href="{{ $leadUrl }}" class="cta-btn">
                    View Lead Profile →
                </a>
            </div>

        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                This alert was sent by <strong>A2A-CRM</strong> because this lead is assigned to you.<br>
                <a href="{{ $leadUrl }}">Manage this lead</a> &middot;
                You can configure alert preferences in your notification settings.
            </p>
            <p style="margin-top:8px;font-size:10px;color:#D1D5DB">A2A Educational CRM &middot; MEETCS Pvt. Ltd.</p>
        </div>

    </div>
</body>
</html>
