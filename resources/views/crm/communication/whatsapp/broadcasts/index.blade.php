<x-layouts.crm title="WhatsApp Broadcasts">
    <div class="space-y-6">

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('crm.communication.whatsapp.index') }}"
                   class="flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   aria-label="Back to WhatsApp Inbox">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 leading-tight">WhatsApp Broadcasts</h1>
                        <p class="mt-0.5 text-sm text-gray-500">Bulk WhatsApp messages to lead segments · BRD CC-015</p>
                    </div>
                </div>
            </div>
            @can('crm.campaigns.send')
                <a href="{{ route('crm.communication.whatsapp.broadcasts.create') }}"
                   class="btn-primary gap-2 self-start sm:self-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Broadcast
                </a>
            @endcan
        </div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800" role="alert">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800" role="alert">
                <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Broadcasts Table --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="table-th">Broadcast Name</th>
                        <th scope="col" class="table-th">Template</th>
                        <th scope="col" class="table-th">Status</th>
                        <th scope="col" class="table-th">Recipients</th>
                        <th scope="col" class="table-th">Launched</th>
                        <th scope="col" class="table-th">Created By</th>
                        <th scope="col" class="table-th text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($broadcasts as $broadcast)
                        @php
                            $colour = $broadcast->status->colour();
                            $badgeClass = match($colour) {
                                'green'  => 'bg-green-100 text-green-800',
                                'yellow' => 'bg-yellow-100 text-yellow-800',
                                'blue'   => 'bg-blue-100 text-blue-800',
                                'red'    => 'bg-red-100 text-red-800',
                                default  => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors duration-100">
                            <td class="table-td">
                                <a href="{{ route('crm.communication.whatsapp.broadcasts.show', $broadcast->uuid) }}"
                                   class="font-medium text-gray-900 hover:text-indigo-600 transition-colors">
                                    {{ $broadcast->name }}
                                </a>
                            </td>
                            <td class="table-td text-gray-500 truncate max-w-[200px]">
                                {{ $broadcast->template?->name ?? '—' }}
                            </td>
                            <td class="table-td">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                    {{ $broadcast->status->label() }}
                                </span>
                            </td>
                            <td class="table-td text-gray-500">
                                {{ $broadcast->lead_count > 0 ? number_format($broadcast->lead_count) : '—' }}
                            </td>
                            <td class="table-td text-gray-500 text-xs">
                                {{ $broadcast->launched_at?->format('d M Y, H:i') ?? '—' }}
                            </td>
                            <td class="table-td text-gray-500 text-xs">
                                {{ $broadcast->creator?->name ?? '—' }}
                            </td>
                            <td class="table-td text-right">
                                <a href="{{ route('crm.communication.whatsapp.broadcasts.show', $broadcast->uuid) }}"
                                   class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-td py-16 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">No broadcasts yet</p>
                                        <p class="mt-1 text-xs text-gray-400">Create your first WhatsApp broadcast to reach leads in bulk.</p>
                                    </div>
                                    @can('crm.campaigns.send')
                                        <a href="{{ route('crm.communication.whatsapp.broadcasts.create') }}" class="btn-primary text-sm mt-1">
                                            New Broadcast
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($broadcasts->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $broadcasts->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.crm>
