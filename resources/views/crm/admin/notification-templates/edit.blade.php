<x-layouts.crm title="Edit Notification Template">
    <div class="space-y-6" x-data="{ channel: '{{ old('channel', $template->channel) }}' }">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Notification Template</h1>
                <p class="mt-1 text-sm text-gray-500">Update template: <strong>{{ $template->name }}</strong></p>
            </div>
            <a href="{{ route('crm.admin.notification-templates.index') }}" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Templates
            </a>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('crm.admin.notification-templates.update', $template) }}">
            @csrf
            @method('PUT')

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
                            <option value="email"     @selected(old('channel', $template->channel) === 'email')>Email</option>
                            <option value="sms"       @selected(old('channel', $template->channel) === 'sms')>SMS</option>
                            <option value="whatsapp"  @selected(old('channel', $template->channel) === 'whatsapp')>WhatsApp</option>
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
                            value="{{ old('name', $template->name) }}"
                            required
                            class="form-input @error('name') border-red-300 @enderror"
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
                            value="{{ old('subject', $template->subject) }}"
                            class="form-input @error('subject') border-red-300 @enderror"
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
                    >{{ old('body', $template->body) }}</textarea>
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
                        placeholder="student_name, course_name, institution_name"
                    >{{ old('merge_tags_json', is_array($template->merge_tags_json) ? implode(', ', $template->merge_tags_json) : $template->merge_tags_json) }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">Comma-separated merge tag keys available in this template.</p>
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
                            {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="form-label mb-0">Active</span>
                    </label>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="{{ route('crm.admin.notification-templates.index') }}" class="btn-secondary">Cancel</a>
                </div>

            </div>
        </form>

    </div>
</x-layouts.crm>
