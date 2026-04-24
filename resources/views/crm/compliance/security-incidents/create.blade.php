<x-layouts.crm title="Report Security Incident">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Report Security Incident</h1>
                <p class="mt-1 text-sm text-gray-500">DPDP Act 2023 — breach notification must be sent within 72h (CR-010)</p>
            </div>
            <a href="{{ route('crm.compliance.security-incidents.index') }}" class="btn-secondary">
                &larr; Back to Incidents
            </a>
        </div>

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
        <div class="card p-6">
            <form method="POST" action="{{ route('crm.compliance.security-incidents.store') }}" class="space-y-5">
                @csrf

                {{-- Incident Type --}}
                <div>
                    <label for="incident_type" class="block text-sm font-medium text-gray-700 mb-1">
                        Incident Type <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                        id="incident_type"
                        name="incident_type"
                        value="{{ old('incident_type') }}"
                        required
                        placeholder="e.g. Unauthorised Access, Data Breach, Ransomware..."
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('incident_type') border-red-400 @enderror">
                    @error('incident_type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        required
                        placeholder="Describe the nature of the incident, what data may have been affected, initial assessment..."
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-400 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Detected At --}}
                <div>
                    <label for="detected_at" class="block text-sm font-medium text-gray-700 mb-1">
                        Detected At <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local"
                        id="detected_at"
                        name="detected_at"
                        value="{{ old('detected_at') }}"
                        required
                        class="block w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('detected_at') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-gray-500">Date and time when the incident was first detected.</p>
                    @error('detected_at')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                    <button type="submit" class="btn-primary">Submit Incident Report</button>
                    <a href="{{ route('crm.compliance.security-incidents.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

    </div>
</x-layouts.crm>
