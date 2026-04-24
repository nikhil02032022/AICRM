<x-layouts.crm title="Edit Institution">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Institution</h1>
                <p class="mt-1 text-sm text-gray-500">Update institution profile and settings</p>
            </div>
            <a href="{{ route('crm.admin.institutions.index') }}" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Institutions
            </a>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('crm.admin.institutions.update', $institution) }}">
            @csrf
            @method('PUT')

            <div class="card p-6 space-y-6">

                <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3">Basic Information</h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Name --}}
                    <div class="form-group sm:col-span-2">
                        <label for="name" class="form-label">Institution Name <span class="text-red-500">*</span></label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $institution->name) }}"
                            required
                            class="form-input @error('name') border-red-300 @enderror"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email', $institution->email) }}"
                            class="form-input @error('email') border-red-300 @enderror"
                        >
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Phone --}}
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone</label>
                        <input
                            id="phone"
                            type="tel"
                            name="phone"
                            value="{{ old('phone', $institution->phone) }}"
                            class="form-input @error('phone') border-red-300 @enderror"
                        >
                        @error('phone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Address --}}
                    <div class="form-group sm:col-span-2">
                        <label for="address" class="form-label">Address</label>
                        <input
                            id="address"
                            type="text"
                            name="address"
                            value="{{ old('address', $institution->address) }}"
                            class="form-input @error('address') border-red-300 @enderror"
                        >
                        @error('address')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- City --}}
                    <div class="form-group">
                        <label for="city" class="form-label">City</label>
                        <input
                            id="city"
                            type="text"
                            name="city"
                            value="{{ old('city', $institution->city) }}"
                            class="form-input @error('city') border-red-300 @enderror"
                        >
                        @error('city')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- State --}}
                    <div class="form-group">
                        <label for="state" class="form-label">State / Province</label>
                        <input
                            id="state"
                            type="text"
                            name="state"
                            value="{{ old('state', $institution->state) }}"
                            class="form-input @error('state') border-red-300 @enderror"
                        >
                        @error('state')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Country --}}
                    <div class="form-group">
                        <label for="country" class="form-label">Country</label>
                        <input
                            id="country"
                            type="text"
                            name="country"
                            value="{{ old('country', $institution->country) }}"
                            class="form-input @error('country') border-red-300 @enderror"
                        >
                        @error('country')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Timezone --}}
                    <div class="form-group">
                        <label for="timezone" class="form-label">Timezone</label>
                        <select id="timezone" name="timezone" class="form-input @error('timezone') border-red-300 @enderror">
                            <option value="">— Select Timezone —</option>
                            @foreach(\DateTimeZone::listIdentifiers() as $tz)
                                <option value="{{ $tz }}" @selected(old('timezone', $institution->timezone) === $tz)>{{ $tz }}</option>
                            @endforeach
                        </select>
                        @error('timezone')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Locale --}}
                    <div class="form-group">
                        <label for="locale" class="form-label">Locale</label>
                        <select id="locale" name="locale" class="form-input @error('locale') border-red-300 @enderror">
                            <option value="">— Select Locale —</option>
                            @foreach(['en' => 'English', 'en_US' => 'English (US)', 'en_GB' => 'English (UK)', 'en_AU' => 'English (AU)', 'fr' => 'French', 'de' => 'German', 'es' => 'Spanish', 'ar' => 'Arabic', 'zh' => 'Chinese', 'hi' => 'Hindi'] as $code => $label)
                                <option value="{{ $code }}" @selected(old('locale', $institution->locale) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('locale')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <h2 class="text-base font-semibold text-gray-800 border-b border-gray-100 pb-3 pt-2">Branding</h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Primary colour --}}
                    <div class="form-group">
                        <label for="primary_color" class="form-label">Primary Colour</label>
                        <div class="flex items-center gap-3">
                            <input
                                id="primary_color"
                                type="color"
                                name="primary_color"
                                value="{{ old('primary_color', $institution->primary_color ?? '#6366f1') }}"
                                class="h-10 w-16 cursor-pointer rounded-lg border border-gray-200 p-1 @error('primary_color') border-red-300 @enderror"
                            >
                            <span class="text-xs text-gray-500">Pick the brand colour for this institution.</span>
                        </div>
                        @error('primary_color')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Logo URL --}}
                    <div class="form-group">
                        <label for="logo_url" class="form-label">Logo URL</label>
                        <input
                            id="logo_url"
                            type="url"
                            name="logo_url"
                            value="{{ old('logo_url', $institution->logo_url) }}"
                            placeholder="https://…"
                            class="form-input @error('logo_url') border-red-300 @enderror"
                        >
                        @error('logo_url')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="{{ route('crm.admin.institutions.index') }}" class="btn-secondary">Cancel</a>
                </div>

            </div>
        </form>

    </div>
</x-layouts.crm>
