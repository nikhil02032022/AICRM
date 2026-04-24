{{-- BRD: CRM-AL-002 — Create a new alumni referral campaign --}}
<x-layouts.crm title="New Referral Campaign">
    <x-slot:header>
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('crm.alumni.referral.campaigns.index') }}" class="hover:text-indigo-600">Alumni Referral</a>
            <span>/</span>
            <span class="text-gray-700 font-medium">New Campaign</span>
        </div>
    </x-slot:header>

    <form method="POST" action="{{ route('crm.alumni.referral.campaigns.store') }}" class="max-w-2xl space-y-6">
        @csrf

        @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4" role="alert">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-800">Please fix the following errors:</p>
                    <ul class="mt-1.5 list-inside list-disc space-y-0.5 text-sm text-red-700">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        {{-- Campaign Details --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-base font-semibold text-gray-900">Campaign Details</h2>
            <div class="grid grid-cols-1 gap-5">
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">Campaign Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                           placeholder="e.g. MBA Batch 2026 Referral Drive">
                    @error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="description" class="mb-1.5 block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('description') border-red-500 @enderror"
                              placeholder="Optional description for internal reference">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Dates --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-base font-semibold text-gray-900">Campaign Duration</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="start_date" class="mb-1.5 block text-sm font-medium text-gray-700">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('start_date') border-red-500 @enderror">
                    @error('start_date')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="end_date" class="mb-1.5 block text-sm font-medium text-gray-700">End Date <span class="text-gray-400 text-xs">(optional)</span></label>
                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('end_date') border-red-500 @enderror">
                    @error('end_date')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Reward --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-base font-semibold text-gray-900">Reward Configuration</h2>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label for="reward_type" class="mb-1.5 block text-sm font-medium text-gray-700">Reward Type <span class="text-red-500">*</span></label>
                    <select id="reward_type" name="reward_type" required
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('reward_type') border-red-500 @enderror">
                        <option value="">Select reward type…</option>
                        @foreach($rewardTypes as $type)
                        <option value="{{ $type->value }}" {{ old('reward_type') === $type->value ? 'selected' : '' }}>
                            {{ $type->label() }}
                        </option>
                        @endforeach
                    </select>
                    @error('reward_type')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="reward_value" class="mb-1.5 block text-sm font-medium text-gray-700">Reward Value (₹) <span class="text-gray-400 text-xs">(optional)</span></label>
                    <input type="number" id="reward_value" name="reward_value" value="{{ old('reward_value') }}" min="0" step="0.01"
                           class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 @error('reward_value') border-red-500 @enderror"
                           placeholder="e.g. 2000">
                    @error('reward_value')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('crm.alumni.referral.campaigns.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">Create Campaign</button>
        </div>
    </form>
</x-layouts.crm>
