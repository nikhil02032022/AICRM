<x-layouts.crm title="Communication Opt-Out">
    <div class="space-y-6 max-w-xl mx-auto">

        {{-- Page Header --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manage Communication Preferences</h1>
            <p class="mt-1 text-sm text-gray-500">Opt out of specific or all communication channels.</p>
        </div>

        {{-- Flash --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                <p class="font-semibold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form Card --}}
        <div
            class="card p-6"
            x-data="{
                channels: {{ json_encode(old('channel', [])) }},
                toggleAll(checked) {
                    if (checked) {
                        this.channels = ['email', 'sms', 'whatsapp', 'all'];
                    } else {
                        this.channels = this.channels.filter(c => c !== 'all');
                    }
                }
            }"
        >
            <form method="POST" action="{{ route('crm.portal.opt-out.store') }}" class="space-y-5">
                @csrf

                {{-- Channels --}}
                <div>
                    <fieldset>
                        <legend class="block text-sm font-medium text-gray-700 mb-3">Select channels to opt out of:</legend>
                        <div class="space-y-3">

                            {{-- Email --}}
                            <label class="flex items-center gap-3 cursor-pointer group" x-bind:class="channels.includes('all') ? 'opacity-60 pointer-events-none' : ''">
                                <input type="checkbox"
                                    name="channel[]"
                                    value="email"
                                    x-model="channels"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="block text-sm font-medium text-gray-900">Email</span>
                                    <span class="block text-xs text-gray-500">Opt out of all marketing and communication emails</span>
                                </div>
                            </label>

                            {{-- SMS --}}
                            <label class="flex items-center gap-3 cursor-pointer group" x-bind:class="channels.includes('all') ? 'opacity-60 pointer-events-none' : ''">
                                <input type="checkbox"
                                    name="channel[]"
                                    value="sms"
                                    x-model="channels"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="block text-sm font-medium text-gray-900">SMS</span>
                                    <span class="block text-xs text-gray-500">Opt out of text message communications</span>
                                </div>
                            </label>

                            {{-- WhatsApp --}}
                            <label class="flex items-center gap-3 cursor-pointer group" x-bind:class="channels.includes('all') ? 'opacity-60 pointer-events-none' : ''">
                                <input type="checkbox"
                                    name="channel[]"
                                    value="whatsapp"
                                    x-model="channels"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <span class="block text-sm font-medium text-gray-900">WhatsApp</span>
                                    <span class="block text-xs text-gray-500">Opt out of WhatsApp messages</span>
                                </div>
                            </label>

                            {{-- Divider --}}
                            <div class="border-t border-gray-200 my-2"></div>

                            {{-- All Communications --}}
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox"
                                    name="channel[]"
                                    value="all"
                                    @change="toggleAll($event.target.checked)"
                                    :checked="channels.includes('all')"
                                    class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                <div>
                                    <span class="block text-sm font-semibold text-gray-900">All Communications</span>
                                    <span class="block text-xs text-red-600">Selecting this will opt you out of every channel.</span>
                                </div>
                            </label>

                        </div>
                        @error('channel')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </div>

                {{-- Info Note --}}
                <div class="rounded-lg bg-yellow-50 border border-yellow-200 px-4 py-3 text-xs text-yellow-800">
                    <strong>Note:</strong> Opt-out requests will be processed within 24 hours (CR-003). Transactional emails related to your application status may still be sent regardless of opt-out preference.
                </div>

                {{-- Submit --}}
                <div class="pt-2 border-t border-gray-100">
                    <button type="submit" class="btn-primary w-full justify-center">Save Preferences</button>
                </div>
            </form>
        </div>

    </div>
</x-layouts.crm>
