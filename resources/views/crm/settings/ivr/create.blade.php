<x-layouts.crm title="Add IVR Configuration">
    <div class="mx-auto max-w-2xl space-y-6">

        <div>
            <a href="{{ route('crm.settings.ivr.index') }}"
               class="mb-3 inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to IVR Configurations
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Add IVR Configuration</h1>
            <p class="mt-1 text-sm text-gray-500">Inbound calls to this virtual number will auto-create leads · BRD CRM-CC-019, CRM-LC-010</p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('crm.settings.ivr.store') }}"
              class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
            @csrf

            {{-- Telephony Provider --}}
            <div>
                <label for="provider" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Telephony Provider <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select id="provider" name="provider" required
                    class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('provider') border-red-500 @enderror">
                    <option value="">Select provider…</option>
                    @foreach (\App\Enums\CRM\TelephonyProvider::cases() as $tp)
                        <option value="{{ $tp->value }}" @selected(old('provider') === $tp->value)>{{ $tp->label() }}</option>
                    @endforeach
                </select>
                @error('provider')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Virtual Number --}}
            <div>
                <label for="virtual_number" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Virtual / DID Number <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input type="text" id="virtual_number" name="virtual_number"
                    value="{{ old('virtual_number') }}" required maxlength="20"
                    placeholder="+91 9900 000000"
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('virtual_number') border-red-500 @enderror">
                <p class="mt-1 text-xs text-gray-400">The inbound DID / virtual number provided by your telephony provider (stored encrypted).</p>
                @error('virtual_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Welcome Message --}}
            <div>
                <label for="welcome_message" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Welcome / IVR Message <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <textarea id="welcome_message" name="welcome_message" rows="3" required maxlength="500"
                    placeholder="Welcome to Admissions. Please stay on the line while we connect you to a counsellor."
                    class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 @error('welcome_message') border-red-500 @enderror">{{ old('welcome_message') }}</textarea>
                <p class="mt-1 text-xs text-gray-400">Text-to-speech greeting read to the caller. Max 500 characters.</p>
                @error('welcome_message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Data Capture Toggles --}}
            <fieldset class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-3">
                <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500 px-1">Collect from Caller</legend>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="collect_name" name="collect_name" value="1"
                        @checked(old('collect_name', true))
                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <label for="collect_name" class="text-sm text-gray-700">Collect caller's name via IVR prompt</label>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" id="collect_programme" name="collect_programme" value="1"
                        @checked(old('collect_programme', true))
                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <label for="collect_programme" class="text-sm text-gray-700">Collect programme of interest via IVR menu</label>
                </div>
            </fieldset>

            {{-- Fallback Counsellor --}}
            <div>
                <label for="fallback_counsellor_id" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Fallback Counsellor
                </label>
                <select id="fallback_counsellor_id" name="fallback_counsellor_id"
                    class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">None (unassigned)</option>
                    @foreach ($counsellors as $user)
                        <option value="{{ $user->id }}" @selected(old('fallback_counsellor_id') === $user->id)>
                            {{ $user->name }} — {{ $user->email }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-400">Gets notified on missed calls and newly created IVR leads.</p>
                @error('fallback_counsellor_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit" class="btn-primary">Save IVR Configuration</button>
                <a href="{{ route('crm.settings.ivr.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>

    </div>
</x-layouts.crm>
