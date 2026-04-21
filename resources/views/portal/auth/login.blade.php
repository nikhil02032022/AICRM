<x-layouts.portal-guest>
    <x-slot:heading>Sign in to your portal</x-slot:heading>

    <form method="POST" action="{{ route('portal.auth.send-otp') }}" class="space-y-5">
        @csrf
        @isset($institution)
            <input type="hidden" name="institution" value="{{ $institution->uuid }}" />
        @endisset

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="email"
                required
                value="{{ old('email') }}"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm
                       focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 portal-ring-primary
                       @error('email') border-red-300 @enderror"
                placeholder="you@example.com"
            />
            @error('email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full rounded-md px-4 py-2 text-sm font-semibold shadow-sm portal-btn-primary
                   transition-opacity focus-visible:outline focus-visible:outline-2"
        >
            Send login code
        </button>
    </form>

    <p class="mt-6 text-center text-xs text-gray-500">
        A 6-digit code will be sent to your registered email address.
    </p>
</x-layouts.portal-guest>
