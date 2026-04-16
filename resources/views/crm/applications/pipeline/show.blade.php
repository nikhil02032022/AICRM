<x-layouts.crm>
    <x-slot:header>Application Detail</x-slot:header>

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Applicant</h2>
            <p class="mt-2 text-sm text-gray-700">{{ $application->lead?->first_name }} {{ $application->lead?->last_name }}</p>
            <p class="text-xs text-gray-500">UUID: {{ $application->uuid }}</p>
        </div>
    </div>
</x-layouts.crm>
