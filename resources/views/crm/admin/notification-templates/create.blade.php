<x-layouts.crm title="New Notification Template">
    <div class="space-y-6" x-data="{ channel: '{{ old('channel', 'email') }}' }">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">New Notification Template</h1>
                <p class="mt-1 text-sm text-gray-500">Create a reusable message template</p>
            </div>
            <a href="{{ route('crm.admin.notification-templates.index') }}" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Templates
            </a>
        </div>

        {{-- Form --}}
        <form method="POST" action="{{ route('crm.admin.notification-templates.store') }}">
            @csrf

            <div class="card p-6 space-y-5">

                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Channel --}}
                    <div class="form-group">
                        <label for="channel" class="form-label">Channel <span class="text-red-500">*</span></label>
                        <select
                            id="channel"
                            name="channel"
                            x-model="channel"
                            class="form-input @error('channel') border-red-300 @enderror"
                            required
                        >
                            <option value="email" @selected(old('channel') === 'email')>Email</option>
                            <option value="sms"   @selected(old('channel') === 'sms')>SMS</option>
                            <option value="whatsapp" @selected(old('channel') === 'whatsapp')>WhatsApp</option>
                        </select>
                        @error('channel')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Name --}}
                    <div class="form-group">
                        <label for="name" class="form-label">Template Name <span class="text-red-500">*</span></label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            class="form-input @error('name') border-red-300 @enderror"
                            placeholder="e.g. Welcome Email"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Subject (email only) --}}
                    <div class="form-group sm:col-span-2" x-show="channel === 'email'" x-cloak>
                        <label for="subject" class="form-label">Subject</label>
                        <input
                            id="subject"
                            type="text"
                            name="subject"
                            value="{{ old('subject') }}"
                            class="form-input @error('subject') border-red-300 @enderror"
                            placeholder="e.g. Welcome to {{ '{{institution_name}}' }}"
                        >
                        @error('subject')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Body --}}
                <div class="form-group">
                    <label for="body" class="form-label">Body <span class="text-red-500">*</span></label>
                    <textarea
                        id="body"
                        name="body"
                        rows="10"
                        required
                        class="form-input font-mono text-sm @error('body') border-red-300 @enderror"
                        placeholder="Use {{ '{{merge_tag}}' }} syntax for dynamic values…"
                    >{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Merge tags --}}
                <div class="form-group">
                    <label for="merge_tags_json" class="form-label">Merge Tags</label>
                    <textarea
                        id="merge_tags_json"
                        name="merge_tags_json"
                        rows="2"
                        class="form-input font-mono text-sm @error('merge_tags_json') border-red-300 @enderror"
                        placeholder="student_name, course_name, institution_name, application_id"
                    >{{ old('merge_tags_json') }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">Enter comma-separated merge tag keys available in this template.</p>
                    @error('merge_tags_json')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Is active --}}
                <div class="form-group">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            id="is_active"
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', '1') ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="form-label mb-0">Active</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-400">Only active templates can be dispatched.</p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                    <button type="submit" class="btn-primary">Create Template</button>
                    <a href="{{ route('crm.admin.notification-templates.index') }}" class="btn-secondary">Cancel</a>
                </div>

            </div>
        </form>

    </div>
</x-layouts.crm>
