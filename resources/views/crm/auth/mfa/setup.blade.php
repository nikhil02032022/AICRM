<x-layouts.crm title="Set Up Two-Factor Authentication">
<div class="max-w-lg mx-auto py-10">
    <div class="card p-8 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Set Up Two-Factor Authentication</h1>
            <p class="mt-1 text-sm text-gray-500">Scan the QR code with an authenticator app (Google Authenticator, Authy, etc.) and enter the 6-digit code to activate MFA.</p>
        </div>

        {{-- QR Code --}}
        <div class="flex justify-center">
            <img src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode($qr_url) }}&size=200x200"
                 alt="MFA QR Code"
                 class="border rounded-lg p-2">
        </div>

        {{-- Manual entry secret --}}
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Manual Entry Key</p>
            <code class="block bg-gray-50 border rounded px-3 py-2 text-sm font-mono tracking-widest text-center select-all">{{ $secret }}</code>
        </div>

        {{-- Recovery codes — shown only once --}}
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <p class="text-sm font-semibold text-amber-800 mb-2">Save Your Recovery Codes</p>
            <p class="text-xs text-amber-700 mb-3">Store these codes somewhere safe. Each code can only be used once to bypass TOTP if you lose access to your authenticator.</p>
            <div class="grid grid-cols-2 gap-1">
                @foreach ($recovery_codes as $code)
                    <code class="text-xs font-mono bg-white border rounded px-2 py-1 text-center">{{ $code }}</code>
                @endforeach
            </div>
        </div>

        {{-- TOTP confirmation form --}}
        <form method="POST" action="{{ route('crm.mfa.enable') }}">
            @csrf
            <div class="form-group">
                <label for="code" class="form-label">Verification Code</label>
                <input type="text"
                       id="code"
                       name="code"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       maxlength="6"
                       class="form-input text-center tracking-widest text-lg @error('code') border-red-500 @enderror"
                       placeholder="000000"
                       autofocus>
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-primary w-full mt-4">Activate Two-Factor Authentication</button>
        </form>
    </div>
</div>
</x-layouts.crm>
