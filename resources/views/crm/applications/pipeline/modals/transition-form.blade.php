<x-layouts.crm>
    <x-slot:header>Transition Application</x-slot:header>

    @php
        $currentStatus = $application->status;
        $nextStatuses = $currentStatus ? $currentStatus->transitionsTo() : [];
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">Transition Application</h2>
        <p class="mt-2 text-sm text-gray-600">Application: {{ $application->uuid }}</p>
        <p class="text-sm text-gray-600">Current status: {{ $application->status?->label() }}</p>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (count($nextStatuses) === 0)
            <p class="mt-4 text-sm text-amber-700">No valid next transitions are available from this status.</p>
        @else
            <form method="POST" action="{{ route('crm.applications.transition.apply', ['application' => $application->uuid]) }}" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label for="status" class="mb-1 block text-sm font-medium text-gray-700">Next Status</label>
                    <select
                        id="status"
                        name="status"
                        required
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    >
                        <option value="">Select status</option>
                        @foreach ($nextStatuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status') === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="reason" class="mb-1 block text-sm font-medium text-gray-700">Reason (Optional)</label>
                    <textarea
                        id="reason"
                        name="reason"
                        rows="3"
                        maxlength="500"
                        class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                        placeholder="Add transition note"
                    >{{ old('reason') }}</textarea>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700"
                    >
                        Update Status
                    </button>

                    <a
                        href="{{ route('crm.applications.show', ['application' => $application->uuid]) }}"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        @endif
    </div>
</x-layouts.crm>
