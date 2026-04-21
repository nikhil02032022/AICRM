<x-layouts.portal-guest>
    <x-slot:heading>Enter your login code</x-slot:heading>

    <form method="POST" action="{{ route('portal.auth.do-verify') }}" class="space-y-5">
        @csrf
        @isset($institution)
            <input type="hidden" name="institution" value="{{ $institution->uuid }}" />
        @endisset
        <input type="hidden" name="email" value="{{ $email }}" />

        <p class="text-sm text-gray-600">
            We sent a 6-digit code to <span class="font-medium text-gray-900">{{ $email }}</span>.
            Enter it below to sign in.
        </p>

        <div>
            <label for="otp" class="block text-sm font-medium text-gray-700">Login code</label>
            <input
                id="otp"
                name="otp"
                type="text"
                inputmode="numeric"
                pattern="\d{6}"
                maxlength="6"
                autocomplete="one-time-code"
                required
                autofocus
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-center
                       text-2xl tracking-widest shadow-sm focus:border-indigo-500 focus:ring-1
                       focus:ring-indigo-500 portal-ring-primary
                       @error('otp') border-red-300 @enderror"
                placeholder="000000"
            />
            @error('otp')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-md px-4 py-2 text-sm font-semibold shadow-sm portal-btn-primary
                   transition-opacity focus-visible:outline focus-visible:outline-2"
        >
            Verify &amp; sign in
        </button>
    </form>

    <p class="mt-5 text-center text-xs text-gray-500">
        Didn't receive a code?
        <a href="{{ route('portal.auth.login') }}" class="portal-text-primary hover:underline">Try again</a>
    </p>
</x-layouts.portal-guest>
