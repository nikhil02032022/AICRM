<x-layouts.crm title="Qualification Questionnaires">
    <x-slot:header>
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-xl font-semibold text-gray-900">Qualification Questionnaires</h1>
            <a href="{{ route('crm.scoring.questionnaires.create') }}" class="btn-primary">Create Questionnaire</a>
        </div>
    </x-slot:header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Questions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($questionnaires as $questionnaire)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $questionnaire->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $questionnaire->status?->label() ?? ucfirst((string) $questionnaire->status) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ count($questionnaire->questions ?? []) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $questionnaire->updated_at?->diffForHumans() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('crm.scoring.questionnaires.edit', $questionnaire->uuid) }}" class="btn-secondary-sm">Edit</a>
                                    <form action="{{ route('crm.scoring.questionnaires.destroy', $questionnaire->uuid) }}" method="POST" onsubmit="return confirm('Archive this questionnaire?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost-sm text-red-600">Archive</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No qualification questionnaires created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $questionnaires->links() }}
        </div>
    </div>
</x-layouts.crm>
