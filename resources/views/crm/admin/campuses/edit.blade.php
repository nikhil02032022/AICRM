<x-layouts.crm title="Edit Campus">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Campus</h1>
                <p class="mt-1 text-sm text-gray-500">Update campus details</p>
            </div>
            <a href="{{ route('crm.admin.campuses.index') }}" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Campuses
            </a>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('crm.admin.campuses.update', $campus) }}">
            @csrf
            @method('PUT')

            <div class="card p-6 space-y-5">

                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Name --}}
                    <div class="form-group">
                        <label for="name" class="form-label">Campus Name <span class="text-red-500">*</span></label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name', $campus->name) }}"
                            required
                            class="form-input @error('name') border-red-300 @enderror"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Code --}}
                    <div class="form-group">
                        <label for="code" class="form-label">Campus Code <span class="text-red-500">*</span></label>
                        <input
                            id="code"
                            type="text"
                            name="code"
                            value="{{ old('code', $campus->code) }}"
                            required
                            maxlength="20"
                            class="form-input font-mono @error('code') border-red-300 @enderror"
                        >
                        <p class="mt-1 text-xs text-gray-400">Up to 20 characters.</p>
                        @error('code')
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
                            value="{{ old('city', $campus->city) }}"
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
                            value="{{ old('state', $campus->state) }}"
                            class="form-input @error('state') border-red-300 @enderror"
                        >
                        @error('state')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Active toggle --}}
                <div class="form-group">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            id="is_active"
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', $campus->is_active) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="form-label mb-0">Active</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-400">Inactive campuses are hidden from selection dropdowns.</p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="{{ route('crm.admin.campuses.index') }}" class="btn-secondary">Cancel</a>
                </div>

            </div>
        </form>

    </div>
</x-layouts.crm>
