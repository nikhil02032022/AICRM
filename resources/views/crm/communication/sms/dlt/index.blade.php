<x-layouts.crm title="DLT Templates">
    <div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">DLT SMS Templates</h1>
                <p class="mt-1 text-sm text-gray-500">TRAI-registered templates required for transactional and marketing SMS</p>
            </div>
            @can('crm.communication.send')
            <a href="{{ route('crm.communication.sms.dlt.templates.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Register Template
            </a>
            @endcan
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full">
                {{-- ── Header ── --}}
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50">
                        <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Template Name</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 w-44">DLT Template ID</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 w-32">Type</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 w-32">Sender ID</th>
                        <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 w-36">Status</th>
                        <th scope="col" class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 w-56">Actions</th>
                    </tr>
                </thead>

                {{-- ── Body ── --}}
                <tbody class="divide-y divide-gray-100">
                    @forelse ($templates as $template)
                    @php
                        $typeColors = match($template->message_type->value) {
                            'OTP'           => ['dot' => 'bg-orange-400',  'text' => 'text-orange-700',  'bg' => 'bg-orange-50'],
                            'TRANSACTIONAL' => ['dot' => 'bg-emerald-400', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-50'],
                            'MARKETING'     => ['dot' => 'bg-violet-400',  'text' => 'text-violet-700',  'bg' => 'bg-violet-50'],
                            'PROMOTIONAL'   => ['dot' => 'bg-blue-400',    'text' => 'text-blue-700',    'bg' => 'bg-blue-50'],
                            default         => ['dot' => 'bg-gray-400',    'text' => 'text-gray-600',    'bg' => 'bg-gray-50'],
                        };
                        $statusColors = match($template->status->value) {
                            'DRAFT'            => ['dot' => 'bg-amber-400',   'text' => 'text-amber-700',   'bg' => 'bg-amber-50'],
                            'PENDING_APPROVAL' => ['dot' => 'bg-blue-400',    'text' => 'text-blue-700',    'bg' => 'bg-blue-50'],
                            'APPROVED'         => ['dot' => 'bg-emerald-400', 'text' => 'text-emerald-700', 'bg' => 'bg-emerald-50'],
                            'REJECTED'         => ['dot' => 'bg-red-400',     'text' => 'text-red-700',     'bg' => 'bg-red-50'],
                            default            => ['dot' => 'bg-gray-400',    'text' => 'text-gray-600',    'bg' => 'bg-gray-50'],
                        };
                        $statusLabel = match($template->status->value) {
                            'PENDING_APPROVAL' => 'Pending',
                            default            => ucfirst(strtolower($template->status->value)),
                        };
                    @endphp
                    <tr class="group hover:bg-gray-50/70 transition-colors duration-100">

                        {{-- Template name --}}
                        <td class="px-6 py-4">
                            <p class="text-sm font-semibold text-gray-900 leading-snug">{{ $template->template_name }}</p>
                        </td>

                        {{-- DLT Template ID --}}
                        <td class="px-4 py-4">
                            <span class="font-mono text-xs text-gray-500 select-all">{{ $template->dlt_template_id ?? '—' }}</span>
                        </td>

                        {{-- Type --}}
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $typeColors['bg'] }} {{ $typeColors['text'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $typeColors['dot'] }}" aria-hidden="true"></span>
                                {{ $template->message_type->value }}
                            </span>
                        </td>

                        {{-- Sender ID --}}
                        <td class="px-4 py-4 text-center">
                            <span class="font-mono text-xs font-medium text-gray-700">{{ $template->sender_id }}</span>
                        </td>

                        {{-- Status --}}
                        <td class="px-4 py-4 text-center">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $statusColors['bg'] }} {{ $statusColors['text'] }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusColors['dot'] }}" aria-hidden="true"></span>
                                {{ $statusLabel }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-0 divide-x divide-gray-200">

                                @if ($template->status->value === 'DRAFT')
                                <form method="POST" action="{{ route('crm.communication.sms.dlt.submit', $template->uuid) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-400 rounded cursor-pointer"
                                            aria-label="Submit {{ $template->template_name }} for approval">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0 1 21.485 12 59.77 59.77 0 0 1 3.27 20.876L5.999 12Zm0 0h7.5"/>
                                        </svg>
                                        Submit for Approval
                                    </button>
                                </form>
                                @endif

                                {{-- Hidden delete form --}}
                                <form id="form-del-dlt-{{ $template->uuid }}"
                                      method="POST"
                                      action="{{ route('crm.communication.sms.dlt.templates.destroy', $template->uuid) }}"
                                      class="hidden">
                                    @csrf @method('DELETE')
                                </form>
                                <button type="button"
                                        @click="$dispatch('confirm-delete', { formId: 'form-del-dlt-{{ $template->uuid }}', itemName: '{{ addslashes($template->template_name) }}' })"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium text-red-500 hover:text-red-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-red-400 rounded cursor-pointer"
                                        aria-label="Delete {{ $template->template_name }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                    </svg>
                                    Delete
                                </button>

                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <svg class="mx-auto h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-gray-500">No DLT templates registered</p>
                            <p class="mt-1 text-xs text-gray-400">Register your first TRAI-approved DLT template to send SMS.</p>
                            @can('crm.communication.send')
                            <a href="{{ route('crm.communication.sms.dlt.templates.create') }}" class="btn-primary-sm mt-5">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Register Template
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($templates->hasPages())
            <div class="border-t border-gray-100 px-6 py-3">
                {{ $templates->links() }}
            </div>
            @endif
        </div>

    </div>

    <x-crm.confirm-modal variant="delete" subtext="The DLT template will be permanently removed and cannot be recovered." />
</x-layouts.crm>
