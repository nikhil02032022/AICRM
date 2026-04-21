{{-- BRD: CRM-FM-004 — Checkout placeholder; production embeds gateway SDK here --}}
<x-layouts.crm title="Checkout">
    <div class="mx-auto max-w-md space-y-4">
        <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50">
                <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900">Payment Checkout</h2>
            <p class="mt-2 text-sm text-gray-600">
                Transaction reference
            </p>
            <p class="mt-1 break-all font-mono text-xs text-gray-500">{{ $transaction_uuid }}</p>

            <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 p-3 text-left">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Sandbox Notice</p>
                <p class="mt-1 text-sm text-amber-900">
                    This is a placeholder. The selected gateway SDK will render here in production.
                </p>
            </div>
        </div>
    </div>
</x-layouts.crm>
