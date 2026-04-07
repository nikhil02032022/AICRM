<x-layouts.auth title="Sign In">

    {{-- Title --}}
    <div class="mb-6 text-center">
        <h2 class="text-xl font-semibold text-gray-800">Welcome back</h2>
        <p class="mt-1 text-sm text-gray-500">Sign in to your CRM account</p>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4">
            <p class="text-sm font-medium text-red-700">{{ $errors->first() }}</p>
        </div>
    @endif

    {{-- Login form --}}
    <form method="POST" action="{{ route('login.post') }}" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        {{-- Email --}}
        <div class="mb-4">
            <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">
                Email address
            </label>
            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autocomplete="email"
                autofocus
                class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400
                       transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20
                       @error('email') border-red-400 focus:border-red-500 focus:ring-red-500/20 @enderror"
                placeholder="you@institution.edu"
            >
        </div>

        {{-- Password --}}
        <div class="mb-6" x-data="{ showPassword: false }">
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <a href="#" class="text-xs text-primary-600 hover:text-primary-800 hover:underline">
                    Forgot password?
                </a>
            </div>
            <div class="relative">
                <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-11 text-sm text-gray-900
                           transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20
                           @error('password') border-red-400 @enderror"
                    placeholder="••••••••"
                >
                {{-- Toggle password visibility --}}
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                >
                    <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <svg x-show="showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="display:none">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.96 9.96 0 012.317-3.95M6.938 6.938A9.962 9.962 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.956 9.956 0 01-1.45 2.614M3 3l18 18"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Remember me + Submit --}}
        <div class="mb-6 flex items-center justify-between">
            <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                Remember me
            </label>
        </div>

        <button
            type="submit"
            :disabled="loading"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5
                   text-sm font-semibold text-white shadow-sm transition
                   hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
                   disabled:cursor-not-allowed disabled:opacity-60"
        >
            <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" style="display:none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            <span x-show="loading" style="display:none">Signing in…</span>
            <span x-show="!loading">Sign in</span>
        </button>

    </form>

    {{-- Divider --}}
    <div class="my-6 flex items-center gap-3">
        <div class="h-px flex-1 bg-gray-200"></div>
        <span class="text-xs text-gray-400">Demo credentials below</span>
        <div class="h-px flex-1 bg-gray-200"></div>
    </div>

    {{-- Demo credentials hint --}}
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800" x-data="{ open: false }">
        <button @click="open = !open" class="flex w-full items-center justify-between font-medium" type="button">
            <span>Quick login — dev only</span>
            <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-collapse class="mt-3 space-y-1" style="display:none">
            @foreach([
                ['Institution Admin',    'admin@demo.edu'],
                ['Admissions Director',  'director@demo.edu'],
                ['Admissions Manager',   'manager@demo.edu'],
                ['Senior Counsellor',    'sr.counsellor@demo.edu'],
                ['Junior Counsellor',    'jr.counsellor@demo.edu'],
                ['Marketing Manager',    'marketing@demo.edu'],
                ['Finance Officer',      'finance@demo.edu'],
            ] as [$role, $email])
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded px-2 py-1 hover:bg-amber-100"
                    onclick="document.getElementById('email').value='{{ $email }}'; document.getElementById('password').value='password';"
                >
                    <span class="font-medium">{{ $role }}</span>
                    <span class="text-amber-600">{{ $email }}</span>
                </button>
            @endforeach
            <p class="mt-2 text-center text-amber-600">All passwords: <strong>password</strong></p>
        </div>
    </div>

</x-layouts.auth>
