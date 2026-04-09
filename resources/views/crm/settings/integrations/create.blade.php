<x-layouts.crm title="Add Integration">
    <div class="mx-auto max-w-2xl space-y-6">

        <div>
            <a href="{{ route('crm.settings.integrations.index') }}"
               class="mb-3 inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Integrations
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Add Channel Integration</h1>
            <p class="mt-1 text-sm text-gray-500">Configure webhook credentials for Google Ads, Meta, or an education portal</p>
        </div>

        <form method="POST" action="{{ route('crm.settings.integrations.store') }}"
              x-data="integrationForm()"
              class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
            @csrf

            @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            {{-- Channel --}}
            <div>
                <label for="channel" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Channel <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <select id="channel" name="channel" x-model="channel" required
                        class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 @error('channel') border-red-500 @enderror">
                    <option value="">Select channel…</option>
                    @foreach($channelOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('channel') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('channel')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Label --}}
            <div>
                <label for="label" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Label <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input type="text" id="label" name="label" value="{{ old('label') }}"
                       required maxlength="200" placeholder="e.g. MBA 2026 Google Campaign"
                       class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 @error('label') border-red-500 @enderror">
                @error('label')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Google Ads fields --}}
            <template x-if="channel === 'google_ads'">
                <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Google Ads Credentials</p>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Webhook Secret</label>
                        <input type="password" name="credentials[webhook_secret]" maxlength="500" autocomplete="off"
                               placeholder="Your Google webhook secret"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">Set this as the webhook secret in your Google Ads Lead Form Extensions setup.</p>
                    </div>
                </div>
            </template>

            {{-- Meta fields --}}
            <template x-if="channel === 'meta'">
                <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Meta Lead Ads Credentials</p>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">App Secret</label>
                        <input type="password" name="credentials[app_secret]" maxlength="500" autocomplete="off"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Page Access Token</label>
                        <input type="password" name="credentials[page_access_token]" maxlength="500" autocomplete="off"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Verify Token</label>
                        <input type="text" name="credentials[verify_token]" maxlength="500" autocomplete="off"
                               placeholder="Any random string you choose"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">Used for the Meta webhook subscription challenge. Enter any secret string.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Page ID</label>
                        <input type="text" name="credentials[page_id]" maxlength="100" autocomplete="off"
                               placeholder="Your Facebook Page numeric ID"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">Numeric ID of the Facebook Page associated with your lead ads.</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Form ID</label>
                        <input type="text" name="credentials[form_id]" maxlength="100" autocomplete="off"
                               placeholder="Meta Lead Form ID (optional)"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">Leave blank to accept leads from all forms on this page.</p>
                    </div>
                </div>
            </template>

            {{-- Portal fields --}}
            <template x-if="['shiksha','college_dekho','careers360','collegedunia'].includes(channel)">
                <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Portal Webhook Credentials</p>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Webhook Secret</label>
                        <input type="password" name="credentials[webhook_secret]" maxlength="500" autocomplete="off"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">Shared secret provided by the portal to verify incoming requests.</p>
                    </div>
                </div>
            </template>

            {{-- Active toggle --}}
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" checked
                       class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="is_active" class="cursor-pointer text-sm text-gray-700">Active — accept webhook events</label>
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                Save Integration
            </button>
        </form>
    </div>

    <script>
    function integrationForm() {
        return { channel: '{{ old('channel', '') }}' };
    }
    </script>
</x-layouts.crm>
