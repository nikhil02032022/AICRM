<x-layouts.crm title="Call Disposition Settings">
    <div class="space-y-6" x-data="{ showAdd: false }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Call Disposition Settings</h1>
                <p class="mt-1 text-sm text-gray-600">Configure which call outcomes are available and which ones trigger follow-up prompts.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="btn-secondary-sm" @click="showAdd = !showAdd">Add Disposition</button>
                <a href="{{ route('crm.communication.voice.index') }}" class="btn-primary-sm">Back to Call Log</a>
            </div>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if ($errors->any())
            <x-alert type="error" :message="$errors->first()" />
        @endif

        <div class="card" x-show="showAdd" x-transition>
            <div class="card-body">
                <h2 class="text-lg font-semibold text-gray-900">Add Disposition</h2>
                <form method="POST" action="{{ route('crm.communication.voice.dispositions.store') }}" class="mt-4 grid gap-4 md:grid-cols-5">
                    @csrf
                    <div class="md:col-span-1">
                        <label class="block text-sm font-medium text-gray-700" for="code">Code</label>
                        <input id="code" name="code" type="text" class="mt-1 block w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700" for="label">Label</label>
                        <input id="label" name="label" type="text" class="mt-1 block w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700" for="sort_order">Order</label>
                        <input id="sort_order" name="sort_order" type="number" min="1" value="1" class="mt-1 block w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    </div>
                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 md:w-auto"
                        >
                            Save
                        </button>
                    </div>
                    <label class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="checkbox" name="is_active" value="1" checked class="h-5 w-5 rounded border-2 border-slate-300 bg-white text-indigo-600 focus:ring-2 focus:ring-indigo-500/30">
                        Active
                    </label>
                    <label class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-gray-700">
                        <input type="checkbox" name="requires_follow_up" value="1" class="h-5 w-5 rounded border-2 border-slate-300 bg-white text-indigo-600 focus:ring-2 focus:ring-indigo-500/30">
                        Requires Follow-up Prompt
                    </label>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="table-th">Code</th>
                            <th class="table-th">Label</th>
                            <th class="table-th">Active</th>
                            <th class="table-th">Follow-up Prompt</th>
                            <th class="table-th">Sort</th>
                            <th class="table-th">Save</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($configs as $config)
                            <tr>
                                <td class="table-td font-semibold text-slate-900">{{ $config->code }}</td>
                                <td class="table-td" colspan="5">
                                    <form method="POST" action="{{ route('crm.communication.voice.dispositions.update', $config->uuid) }}" class="grid gap-3 md:grid-cols-5">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="label" value="{{ $config->label }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" required>
                                        <label class="inline-flex min-h-11 items-center gap-2 text-sm text-slate-700">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" {{ $config->is_active ? 'checked' : '' }} class="h-5 w-5 rounded border-2 border-slate-300 bg-white text-indigo-600 focus:ring-2 focus:ring-indigo-500/30">
                                            Active
                                        </label>
                                        <label class="inline-flex min-h-11 items-center gap-2 text-sm text-slate-700">
                                            <input type="hidden" name="requires_follow_up" value="0">
                                            <input type="checkbox" name="requires_follow_up" value="1" {{ $config->requires_follow_up ? 'checked' : '' }} class="h-5 w-5 rounded border-2 border-slate-300 bg-white text-indigo-600 focus:ring-2 focus:ring-indigo-500/30">
                                            Follow-up Prompt
                                        </label>
                                        <input type="number" name="sort_order" min="1" value="{{ $config->sort_order }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                        <button
                                            type="submit"
                                            class="inline-flex min-h-11 w-full items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 md:w-auto"
                                        >
                                            Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-td py-8 text-center text-slate-500">No dispositions configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $configs->links() }}</div>
            </div>
        </div>
    </div>
</x-layouts.crm>
