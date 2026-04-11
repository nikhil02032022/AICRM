<x-layouts.crm>
    <x-slot:header>Walk-in Kiosk</x-slot:header>

    <x-slot:headerActions>
        <a href="{{ $kioskUrl }}" target="_blank" rel="noopener noreferrer"
           class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
            Open Kiosk
        </a>
    </x-slot:headerActions>

    <div class="space-y-6" x-data="{ copied: false }">
        <section class="relative overflow-hidden rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-6 shadow-sm sm:p-8">
            <div class="pointer-events-none absolute -right-12 -top-12 h-36 w-36 rounded-full bg-amber-200/40 blur-3xl"></div>

            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-700">LC-013 Marketing Capture</p>
                    <h2 class="mt-2 text-2xl font-semibold text-gray-900 sm:text-3xl">Touch-friendly kiosk for walk-in enquiries</h2>
                    <p class="mt-3 text-sm leading-relaxed text-gray-600 sm:text-base">
                        Deploy this kiosk on event tablets or campus desks to capture walk-in prospects instantly with DPDP consent and automatic lead creation.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a href="{{ $kioskUrl }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex min-h-12 min-w-[152px] items-center justify-center whitespace-nowrap rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Launch Kiosk
                    </a>
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ $kioskUrl }}'); copied = true; setTimeout(() => copied = false, 2200);"
                            class="inline-flex min-h-12 min-w-[152px] items-center justify-center whitespace-nowrap rounded-2xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-800 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:border-gray-400 hover:bg-gray-50 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Copy Kiosk URL
                    </button>
                </div>
            </div>

            <p x-show="copied" class="mt-3 text-sm font-medium text-emerald-700" x-cloak>URL copied to clipboard.</p>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-semibold text-gray-900">Deployment URLs</h3>
            <div class="mt-4 space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kiosk Launch URL</p>
                    <p class="mt-1 break-all text-sm font-medium text-gray-800">{{ $kioskUrl }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Kiosk Submit Endpoint</p>
                    <p class="mt-1 break-all text-sm font-medium text-gray-800">{{ $submitUrl }}</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    Institution: {{ $institution->name }}. Capture source is auto-tagged as walk_in with kiosk attribution markers.
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900">Recent Kiosk Captures</h3>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $kioskLeads->total() }} total</span>
            </div>

            @if($kioskLeads->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-sm font-medium text-gray-700">No kiosk leads captured yet.</p>
                    <p class="mt-1 text-sm text-gray-500">Open the kiosk link on a device and submit a sample enquiry.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Lead</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Source</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Query Summary</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Captured At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($kioskLeads as $lead)
                                <tr class="transition-colors hover:bg-amber-50/30">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <a href="{{ route('crm.leads.show', $lead->uuid) }}" class="font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                            {{ $lead->fullName() }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $lead->source?->label() ?? 'Walk-In' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $lead->notes ?: 'Walk-in enquiry captured via kiosk.' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $lead->created_at?->format('d M Y, h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($kioskLeads->hasPages())
                    <div class="border-t border-gray-100 bg-gray-50 px-6 py-3">
                        {{ $kioskLeads->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
</x-layouts.crm>