<x-layouts.crm>
    <x-slot:header>Application Pipeline</x-slot:header>

    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Group N · AP-011</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Pipeline board with live seat visibility</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                        Monitor application movement across stages and compare programme intake capacity against live application volume without leaving the admissions pipeline.
                    </p>
                </div>

                <a
                    href="{{ route('crm.applications.list') }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    Open List View
                </a>
            </div>
        </div>

        @livewire('crm.application.application-pipeline-board')
    </div>
</x-layouts.crm>
