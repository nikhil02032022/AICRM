<x-layouts.crm title="Edit Academic Year">
    <div class="space-y-6">

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Academic Year</h1>
                <p class="mt-1 text-sm text-gray-500">Update academic year details</p>
            </div>
            <a href="{{ route('crm.admin.academic-years.index') }}" class="btn-secondary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Academic Years
            </a>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('crm.admin.academic-years.update', $academicYear) }}">
            @csrf
            @method('PUT')

            <div class="card p-6 space-y-5">

                <div class="grid gap-5 sm:grid-cols-2">
                    {{-- Label --}}
                    <div class="form-group sm:col-span-2">
                        <label for="label" class="form-label">Label <span class="text-red-500">*</span></label>
                        <input
                            id="label"
                            type="text"
                            name="label"
                            value="{{ old('label', $academicYear->label) }}"
                            required
                            class="form-input @error('label') border-red-300 @enderror"
                        >
                        @error('label')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Start date --}}
                    <div class="form-group">
                        <label for="start_date" class="form-label">Start Date <span class="text-red-500">*</span></label>
                        <input
                            id="start_date"
                            type="date"
                            name="start_date"
                            value="{{ old('start_date', $academicYear->start_date->format('Y-m-d')) }}"
                            required
                            class="form-input @error('start_date') border-red-300 @enderror"
                        >
                        @error('start_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- End date --}}
                    <div class="form-group">
                        <label for="end_date" class="form-label">End Date <span class="text-red-500">*</span></label>
                        <input
                            id="end_date"
                            type="date"
                            name="end_date"
                            value="{{ old('end_date', $academicYear->end_date->format('Y-m-d')) }}"
                            required
                            class="form-input @error('end_date') border-red-300 @enderror"
                        >
                        @error('end_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Is active --}}
                <div class="form-group">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            id="is_active"
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', $academicYear->is_active) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span class="form-label mb-0">Set as Active Academic Year</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-400">Only one academic year can be active at a time.</p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="{{ route('crm.admin.academic-years.index') }}" class="btn-secondary">Cancel</a>
                </div>

            </div>
        </form>

    </div>
</x-layouts.crm>
