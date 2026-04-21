{{-- BRD: CRM-FM-001, CRM-FM-002 — Fee structure management --}}
<x-layouts.crm title="Fee Structures">
    <div class="space-y-4" x-data="{ creating: false }">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Fee Structures</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Configure programme-wise application, seat-booking and tuition advance fees.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="creating = !creating"
                    class="btn-primary-sm inline-flex items-center gap-1.5"
                    aria-expanded="false"
                    :aria-expanded="creating.toString()">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span x-text="creating ? 'Cancel' : 'New Fee Structure'"></span>
                </button>
            </div>
        </div>

        {{-- Flash --}}
        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        {{-- Create form (collapsible) --}}
        <div
            x-show="creating"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 text-sm font-semibold text-gray-900">Add Fee Structure</h3>

            <form method="POST" action="{{ route('crm.payments.fee-structures.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="programme_id" class="block text-xs font-medium text-gray-700 mb-1">
                            Programme <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select id="programme_id" name="programme_id" required
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                                   @error('programme_id') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror">
                            <option value="">Select programme</option>
                            @foreach ($programmes as $programme)
                                <option value="{{ $programme->id }}" @selected(old('programme_id') == $programme->id)>
                                    {{ $programme->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('programme_id')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fee_type" class="block text-xs font-medium text-gray-700 mb-1">
                            Fee Type <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select id="fee_type" name="fee_type" required
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            @foreach (\App\Enums\CRM\Payments\FeeType::cases() as $ft)
                                <option value="{{ $ft->value }}" @selected(old('fee_type') === $ft->value)>{{ $ft->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="amount" class="block text-xs font-medium text-gray-700 mb-1">
                            Amount <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0" required value="{{ old('amount') }}"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500
                                   @error('amount') border-red-500 focus:border-red-500 focus:ring-red-500 @enderror"
                            placeholder="1500.00" />
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="currency" class="block text-xs font-medium text-gray-700 mb-1">Currency</label>
                        <input id="currency" name="currency" type="text" maxlength="3" value="{{ old('currency', 'INR') }}"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm uppercase text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="creating = false" class="btn-ghost-sm">Cancel</button>
                    <button type="submit" class="btn-primary-sm inline-flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save
                    </button>
                </div>
            </form>
        </div>

        {{-- Table --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Programme</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Fee Type</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Currency</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($items as $item)
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $item->programme?->name ?? '#'.$item->programme_id }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700">
                                        {{ $item->fee_type?->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ number_format((float) $item->amount, 2) }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-600">{{ $item->currency }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($item->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-green-500" aria-hidden="true"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400" aria-hidden="true"></span>
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('crm.payments.fee-structures.toggle', $item) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="btn-secondary-sm inline-flex items-center gap-1.5"
                                                aria-label="Toggle status for {{ $item->programme?->name }}">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Toggle
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-16 text-center">
                                    <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-500">No fee structures yet</p>
                                    <p class="mt-1 text-xs text-gray-400">Use “New Fee Structure” to add one.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())
                <div class="border-t border-gray-200 px-6 py-3">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.crm>
