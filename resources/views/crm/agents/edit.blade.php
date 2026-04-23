{{-- BRD: CRM-AG-001 — Edit agent profile --}}
<x-layouts.crm title="Edit Agent">
    <x-slot:header>
        <div class="flex items-center gap-3">
            <a href="{{ route('crm.agents.index') }}" class="text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
            <h1 class="text-xl font-semibold text-gray-900">Edit Agent — {{ $agent->name }}</h1>
        </div>
    </x-slot:header>

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('crm.agents.update', $agent) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Profile</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $agent->name) }}" required
                           class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $agent->email) }}" required
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mobile</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $agent->mobile) }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Password <span class="text-gray-400 text-xs">(leave blank to keep current)</span></label>
                        <input type="password" name="password" autocomplete="new-password"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500">
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input type="password" name="password_confirmation"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $agent->status->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Agreement</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date <span class="text-red-500">*</span></label>
                        <input type="date" name="agreement_start" value="{{ old('agreement_start', $agent->agreement_start->toDateString()) }}" required
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="agreement_end" value="{{ old('agreement_end', $agent->agreement_end?->toDateString()) }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea name="notes" rows="3" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('notes', $agent->notes) }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <form method="POST" action="{{ route('crm.agents.destroy', $agent) }}" onsubmit="return confirm('Deactivate this agent?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:underline">Deactivate Agent</button>
                </form>
                <div class="flex gap-3">
                    <a href="{{ route('crm.agents.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</x-layouts.crm>
