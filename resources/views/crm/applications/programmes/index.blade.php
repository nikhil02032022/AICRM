<x-layouts.crm>
    <x-slot:header>Programme Catalogue</x-slot:header>

    <x-slot:headerActions>
        <a href="{{ route('crm.applications.forms.index') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
            Back to Forms
        </a>
    </x-slot:headerActions>

    @if (session('success'))
        <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4">
            <ul class="list-inside list-disc text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Add Programme</h2>
            <p class="mt-1 text-xs text-gray-600">These programmes appear in AP-005 multi-programme selection.</p>

            <form method="POST" action="{{ route('crm.applications.programmes.store') }}" class="mt-4 space-y-3">
                @csrf

                <div>
                    <label for="name" class="mb-1 block text-xs font-medium text-gray-600">Name</label>
                    <input id="name" name="name" type="text" required value="{{ old('name') }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label for="code" class="mb-1 block text-xs font-medium text-gray-600">Code</label>
                    <input id="code" name="code" type="text" value="{{ old('code') }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label for="level" class="mb-1 block text-xs font-medium text-gray-600">Level</label>
                    <input id="level" name="level" type="text" placeholder="UG/PG" value="{{ old('level') }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label for="department" class="mb-1 block text-xs font-medium text-gray-600">Department</label>
                    <input id="department" name="department" type="text" value="{{ old('department') }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label for="erp_programme_uuid" class="mb-1 block text-xs font-medium text-gray-600">ERP Programme UUID (optional)</label>
                    <input id="erp_programme_uuid" name="erp_programme_uuid" type="text" value="{{ old('erp_programme_uuid') }}"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono">
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                    Active
                </label>

                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Add Programme
                </button>
            </form>
        </div>

        <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Code</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Level</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($programmes as $programme)
                            <tr>
                                <td class="px-5 py-3 text-sm font-medium text-gray-900">{{ $programme->name }}</td>
                                <td class="px-5 py-3 text-sm text-gray-700">{{ $programme->code ?? '-' }}</td>
                                <td class="px-5 py-3 text-sm text-gray-700">{{ $programme->level ?? '-' }}</td>
                                <td class="px-5 py-3">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-green-100 text-green-700' => $programme->is_active,
                                        'bg-gray-100 text-gray-700' => !$programme->is_active,
                                    ])>
                                        {{ $programme->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-500">No programmes found. Add at least one to use AP-005 selection.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3">
                {{ $programmes->links() }}
            </div>
        </div>
    </div>
</x-layouts.crm>
