<x-layouts.crm title="Notification Templates">
    <div
        class="space-y-6"
        x-data="{ channel: 'all' }"
    >

        {{-- Page header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Notification Templates</h1>
                <p class="mt-1 text-sm text-gray-500">Manage email, SMS and WhatsApp message templates</p>
            </div>
            <a href="{{ route('crm.admin.notification-templates.create') }}" class="btn-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Template
            </a>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif

        {{-- Channel filter tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-1" aria-label="Channel filter">
                @foreach(['all' => 'All', 'email' => 'Email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $val => $lbl)
                    <button
                        type="button"
                        @click="channel = '{{ $val }}'"
                        :class="channel === '{{ $val }}'
                            ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold'
                            : 'border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap px-4 py-2.5 text-sm transition-colors"
                    >{{ $lbl }}</button>
                @endforeach
            </nav>
        </div>

        {{-- Table --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="table-th">Name</th>
                            <th class="table-th">Channel</th>
                            <th class="table-th">Subject</th>
                            <th class="table-th">Active</th>
                            <th class="table-th">Created</th>
                            <th class="table-th text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 bg-white">
                        @forelse($templates as $template)
                            <tr
                                class="hover:bg-gray-50 transition-colors"
                                x-show="channel === 'all' || channel === '{{ $template->channel }}'"
                            >
                                <td class="table-td font-medium text-gray-900">{{ $template->name }}</td>
                                <td class="table-td">
                                    @if($template->channel === 'email')
                                        <span class="badge-green">Email</span>
                                    @elseif($template->channel === 'sms')
                                        <span class="badge-yellow">SMS</span>
                                    @elseif($template->channel === 'whatsapp')
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">WhatsApp</span>
                                    @else
                                        <span class="badge-gray">{{ ucfirst($template->channel) }}</span>
                                    @endif
                                </td>
                                <td class="table-td text-gray-600 max-w-xs truncate">
                                    {{ $template->subject ?? '—' }}
                                </td>
                                <td class="table-td">
                                    @if($template->is_active)
                                        <span class="badge-green">Active</span>
                                    @else
                                        <span class="badge-gray">Inactive</span>
                                    @endif
                                </td>
                                <td class="table-td text-gray-500 text-sm whitespace-nowrap">
                                    {{ $template->created_at->format('d M Y') }}
                                </td>
                                <td class="table-td text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Preview --}}
                                        <a
                                            href="{{ route('crm.admin.notification-templates.show', $template) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                            title="Preview"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            Preview
                                        </a>
                                        {{-- Edit --}}
                                        <a
                                            href="{{ route('crm.admin.notification-templates.edit', $template) }}"
                                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        {{-- Delete --}}
                                        <form
                                            method="POST"
                                            action="{{ route('crm.admin.notification-templates.destroy', $template) }}"
                                            onsubmit="return confirm('Delete template \'{{ addslashes($template->name) }}\'?')"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 shadow-sm hover:bg-red-50 transition-colors"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-td text-center text-gray-400 py-10">
                                    No notification templates found.
                                    <a href="{{ route('crm.admin.notification-templates.create') }}" class="text-indigo-600 hover:underline ml-1">Create the first one.</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($templates->hasPages())
                <div class="border-t border-gray-100 px-5 py-3">
                    {{ $templates->links() }}
                </div>
            @endif
        </div>

    </div>
</x-layouts.crm>
