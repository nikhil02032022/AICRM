{{-- BRD: CRM-DM-003, CRM-DM-004 — Reviewer inbox --}}
<x-layouts.crm title="Document Review">
    <div class="space-y-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Document Review</h2>
            <p class="mt-1 text-sm text-gray-600">Approve, reject, or request re-upload for submitted documents.</p>
        </div>

        @if (session('status'))
            <div class="rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-b border-gray-200 bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Application</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Document</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Uploaded via</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Size</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($documents as $doc)
                            <tr class="transition-colors hover:bg-gray-50">
                                <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ Str::of($doc->application_uuid)->limit(8, '') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $doc->item?->label ?? $doc->item?->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $doc->uploaded_via?->label() }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ number_format(($doc->size_bytes ?? 0) / 1024, 1) }} KB</td>
                                <td class="px-6 py-4">
                                    @if ($doc->status?->isVerified())
                                        <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">{{ $doc->status?->label() }}</span>
                                    @elseif ($doc->status?->isTerminal())
                                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">{{ $doc->status?->label() }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800">{{ $doc->status?->label() }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('crm.documents.download', $doc) }}" class="btn-ghost-sm">Download</a>
                                        <form method="POST" action="{{ route('crm.documents.decide', $doc) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="decision" value="approve" />
                                            <button type="submit" class="btn-primary-sm">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('crm.documents.decide', $doc) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="decision" value="reject" />
                                            <input type="hidden" name="reason" value="Rejected from review queue" />
                                            <button type="submit" class="btn-secondary-sm">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-16 text-center text-sm text-gray-500">No documents pending review.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($documents->hasPages())
                <div class="border-t border-gray-200 px-6 py-3">{{ $documents->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.crm>
