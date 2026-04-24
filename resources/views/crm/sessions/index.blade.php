{{-- BRD: CRM-EC-015 — Session list for a lead --}}
<x-layouts.crm title="Counselling Sessions">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Sessions</h3>
        @can('crm.sessions.create')
            <a href="{{ route('crm.leads.sessions.create', $lead) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                Book Session
            </a>
        @endcan
    </div>

                    @if($sessions->isEmpty())
                        <p class="text-gray-500 dark:text-gray-400 text-sm">No sessions have been scheduled yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Scheduled At</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Mode</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Counsellor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($sessions as $session)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $session->scheduled_at?->format('d M Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $session->session_type?->label() }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ ucfirst($session->mode) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $session->counsellor?->name ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $session->status->badgeColour() }}-100 text-{{ $session->status->badgeColour() }}-800">
                                                    {{ $session->status->label() }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                {{-- BRD: CRM-EC-018 — Join Video Call button when meeting link is set --}}
                                                @if ($session->meeting_link && $session->meeting_provider?->value !== 'none')
                                                    <a href="{{ $session->meeting_link }}"
                                                       target="_blank"
                                                       rel="noopener noreferrer"
                                                       class="mr-3 text-indigo-600 hover:text-indigo-900">
                                                        Join {{ $session->meeting_provider?->label() ?? 'Video' }}
                                                    </a>
                                                @endif
                                                @can('crm.sessions.edit')
                                                    <a href="#" class="text-indigo-600 hover:text-indigo-900 mr-3">Update Outcome</a>
                                                @endcan
                                                @can('crm.sessions.cancel')
                                                    {{-- Hidden cancel form --}}
                                                    <form id="form-cancel-sess-{{ $session->uuid }}"
                                                          method="POST"
                                                          action="{{ route('crm.sessions.destroy', $session) }}"
                                                          class="hidden">
                                                        @csrf @method('DELETE')
                                                    </form>
                                                    <button type="button"
                                                            @click="$dispatch('confirm-cancel', { formId: 'form-cancel-sess-{{ $session->uuid }}', itemName: 'session on {{ $session->scheduled_at?->format(\'d M Y H:i\') ?? \'this session\' }}' })"
                                                            class="text-red-600 hover:text-red-900 cursor-pointer text-sm font-medium">
                                                        Cancel
                                                    </button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $sessions->links() }}
                        </div>
                    @endif

        </div>
    </div>

    <x-crm.confirm-modal
        variant="cancel"
        title="Cancel this session?"
        subtext="The counselling session will be cancelled."
        confirm-label="Yes, cancel session"
    />
</x-layouts.crm>
