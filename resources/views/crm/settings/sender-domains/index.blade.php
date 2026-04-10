<x-layouts.crm title="Sender Domains">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold leading-tight text-gray-900">Sender Domains</h1>
                <p class="mt-1 text-sm text-gray-500">Verify domains to send email from custom addresses · BRD CC-004</p>
            </div>
            @can('crm.settings.manage')
            <a href="{{ route('crm.settings.sender-domains.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Domain
            </a>
            @endcan
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif
        @if (session('warning'))
            <x-alert type="warning" :message="session('warning')" />
        @endif

        {{-- Table card --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">

                    {{-- Fixed column widths --}}
                    <colgroup>
                        <col class="w-auto" />       {{-- Domain — expands --}}
                        <col style="width:72px" />   {{-- SPF --}}
                        <col style="width:72px" />   {{-- DKIM --}}
                        <col style="width:80px" />   {{-- DMARC --}}
                        <col style="width:110px" />  {{-- Status --}}
                        <col style="width:220px" />  {{-- Actions --}}
                    </colgroup>

                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Domain
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                SPF
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                DKIM
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                DMARC
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($domains as $domain)
                        <tr class="transition-colors duration-150 hover:bg-gray-50">

                            {{-- Domain --}}
                            <td class="px-6 py-4">
                                <span class="font-mono text-sm font-semibold text-gray-900">{{ $domain->domain }}</span>
                                @if($domain->default_from_email)
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $domain->default_from_name }} &lt;{{ $domain->default_from_email }}&gt;</p>
                                @endif
                            </td>

                            {{-- SPF --}}
                            <td class="px-3 py-4 text-center">
                                @if($domain->spf_verified)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100" aria-label="SPF verified">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-red-100" aria-label="SPF not verified">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                @endif
                            </td>

                            {{-- DKIM --}}
                            <td class="px-3 py-4 text-center">
                                @if($domain->dkim_verified)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100" aria-label="DKIM verified">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-red-100" aria-label="DKIM not verified">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                @endif
                            </td>

                            {{-- DMARC --}}
                            <td class="px-3 py-4 text-center">
                                @if($domain->dmarc_verified)
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100" aria-label="DMARC verified">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-red-100" aria-label="DMARC not verified">
                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-3 py-4 text-center">
                                @if($domain->isFullyVerified())
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        <svg class="h-3 w-3 shrink-0" fill="currentColor" viewBox="0 0 8 8" aria-hidden="true">
                                            <circle cx="4" cy="4" r="3"/>
                                        </svg>
                                        Verified
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                        <svg class="h-3 w-3 shrink-0 animate-pulse" fill="currentColor" viewBox="0 0 8 8" aria-hidden="true">
                                            <circle cx="4" cy="4" r="3"/>
                                        </svg>
                                        Pending
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-1">
                                    {{-- DNS Records --}}
                                    <a href="{{ route('crm.settings.sender-domains.show', $domain->uuid) }}"
                                       class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-indigo-600 transition-colors duration-150 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        DNS Records
                                    </a>

                                    <span class="text-gray-200" aria-hidden="true">|</span>

                                    {{-- Re-check --}}
                                    <form method="POST"
                                          action="{{ route('crm.settings.sender-domains.check-dns', $domain->uuid) }}"
                                          class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-blue-600 transition-colors duration-150 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 cursor-pointer">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            Re-check
                                        </button>
                                    </form>

                                    @can('crm.settings.manage')
                                    <span class="text-gray-200" aria-hidden="true">|</span>

                                    {{-- Hidden remove form --}}
                                    <form id="form-del-dom-{{ $domain->uuid }}"
                                          method="POST"
                                          action="{{ route('crm.settings.sender-domains.destroy', $domain->uuid) }}"
                                          class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                    {{-- Remove --}}
                                    <button type="button"
                                            @click="$dispatch('confirm-delete', { formId: 'form-del-dom-{{ $domain->uuid }}', itemName: '{{ addslashes($domain->domain) }}' })"
                                            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-red-500 transition-colors duration-150 hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 cursor-pointer">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Remove
                                    </button>
                                    @endcan
                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-medium text-gray-900">No sender domains yet</p>
                                <p class="mt-1 text-xs text-gray-500">Add and verify a domain to start sending campaigns.</p>
                                @can('crm.settings.manage')
                                <a href="{{ route('crm.settings.sender-domains.create') }}"
                                   class="mt-4 inline-flex btn-primary-sm">
                                    Add your first domain
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($domains->hasPages())
            <div class="border-t border-gray-100 px-6 py-3">
                {{ $domains->links() }}
            </div>
            @endif
        </div>

    </div>

    <x-crm.confirm-modal variant="delete" title="Remove sender domain?" subtext="DNS records for this domain will no longer be recognised for outbound email." confirm-label="Yes, remove domain" />
</x-layouts.crm>

