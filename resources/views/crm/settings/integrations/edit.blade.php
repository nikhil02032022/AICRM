<x-layouts.crm title="Edit Integration">
    <div class="mx-auto max-w-2xl space-y-6">

        <div>
            <a href="{{ route('crm.settings.integrations.index') }}"
               class="mb-3 inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Integrations
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Edit Integration</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $integration->label }} · {{ $integration->channel->label() }}</p>
        </div>

        <form method="POST" action="{{ route('crm.settings.integrations.update', $integration->uuid) }}"
              class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
            @csrf
            @method('PUT')

            @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            {{-- Channel (read-only on edit) --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Channel</label>
                <input type="hidden" name="channel" value="{{ $integration->channel->value }}">
                <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">
                    {{ $integration->channel->label() }}
                </div>
            </div>

            {{-- Label --}}
            <div>
                <label for="label" class="mb-1.5 block text-sm font-medium text-gray-700">
                    Label <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <input type="text" id="label" name="label"
                       value="{{ old('label', $integration->label) }}"
                       required maxlength="200"
                       class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 @error('label') border-red-500 @enderror">
                @error('label')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Credential fields — channel-dependent (mirrors create form) --}}
            <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Credentials — leave blank to keep existing values
                </p>

                @if($integration->channel === \App\Enums\CRM\IntegrationChannel::META)
                    {{-- Meta: App Secret, Page Access Token, Verify Token, Page ID, Form ID --}}
                    @foreach(['app_secret' => 'App Secret', 'page_access_token' => 'Page Access Token', 'verify_token' => 'Verify Token', 'page_id' => 'Page ID', 'form_id' => 'Form ID'] as $key => $fieldLabel)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">{{ $fieldLabel }}</label>
                        <input type="{{ in_array($key, ['verify_token', 'page_id', 'form_id']) ? 'text' : 'password' }}"
                               name="credentials[{{ $key }}]" maxlength="{{ in_array($key, ['page_id', 'form_id']) ? '100' : '500' }}" autocomplete="off"
                               placeholder="Leave blank to keep existing"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        @if($key === 'verify_token')
                        <p class="mt-1 text-xs text-gray-500">Used for the Meta webhook subscription challenge.</p>
                        @elseif($key === 'page_id')
                        <p class="mt-1 text-xs text-gray-500">Numeric ID of the Facebook Page associated with your lead ads.</p>
                        @elseif($key === 'form_id')
                        <p class="mt-1 text-xs text-gray-500">Leave blank to accept leads from all forms on this page.</p>
                        @endif
                    </div>
                    @endforeach
                @else
                    {{-- Google Ads + all portals: Webhook Secret only --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Webhook Secret</label>
                        <input type="password" name="credentials[webhook_secret]" maxlength="500" autocomplete="off"
                               placeholder="Leave blank to keep existing"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                @endif
            </div>

            {{-- Active toggle --}}
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1"
                       @checked(old('is_active', $integration->is_active))
                       class="h-4 w-4 cursor-pointer rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <label for="is_active" class="cursor-pointer text-sm text-gray-700">Active — accept webhook events</label>
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                Update Integration
            </button>
        </form>
    </div>
</x-layouts.crm>
