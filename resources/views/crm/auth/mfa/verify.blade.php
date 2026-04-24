<x-layouts.crm title="Two-Factor Authentication">
<div class="max-w-sm mx-auto py-20">
    <div class="card p-8 space-y-6">
        <div class="text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-indigo-100 flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">Two-Factor Verification</h1>
            <p class="mt-1 text-sm text-gray-500">Enter the 6-digit code from your authenticator app, or a recovery code.</p>
        </div>

        <form method="POST" action="{{ route('crm.mfa.verify') }}">
            @csrf
            <div class="form-group">
                <label for="code" class="form-label">Verification Code</label>
                <input type="text"
                       id="code"
                       name="code"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       maxlength="10"
                       class="form-input text-center tracking-widest text-lg @error('code') border-red-500 @enderror"
                       placeholder="000000"
                       autofocus>
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-primary w-full mt-4">Verify</button>
        </form>

        <p class="text-center text-xs text-gray-400">
            Lost your device? Use a recovery code above (format: XXXX-XXXX).
        </p>
    </div>
</div>
</x-layouts.crm>
