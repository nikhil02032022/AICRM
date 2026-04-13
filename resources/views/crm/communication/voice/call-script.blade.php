<x-layouts.crm title="Call Scripts">
    @php
        $fieldClass = 'mt-1.5 block w-full rounded-lg border-2 border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30';
        $fieldErrorClass = 'border-red-500 focus:border-red-500 focus:ring-red-500/30';
    @endphp
    <div class="space-y-6" x-data="{ showCreate: false }">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Call Scripts</h1>
                <p class="mt-1 text-sm text-gray-500">Manage telecalling scripts and validate branch logic before live calls.</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="btn-secondary-sm" @click="showCreate = !showCreate" aria-label="Toggle create script panel">
                    New Script
                </button>
                <a href="{{ route('crm.communication.voice.index') }}" class="btn-primary-sm">Back to Call Log</a>
            </div>
        </div>

        @if (session('success'))
            <x-alert type="success" :message="session('success')" />
        @endif

        @if ($errors->any())
            <x-alert type="error" message="Please fix the highlighted fields and try again." />
        @endif

        <div class="grid gap-6 lg:grid-cols-5">
            <aside class="card lg:col-span-2">
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Saved Scripts</h2>
                        <span class="text-xs text-gray-500">{{ $scripts->total() }} total</span>
                    </div>

                    <div class="space-y-2">
                        @forelse ($scripts as $script)
                            <a
                                href="{{ route('crm.communication.voice.scripts.show', $script->uuid) }}"
                                @class([
                                    'block rounded-lg border px-3 py-2 transition duration-150',
                                    'border-indigo-300 bg-indigo-50' => $activeScript && $activeScript->id === $script->id,
                                    'border-gray-200 bg-white hover:border-indigo-200 hover:bg-indigo-50/40' => ! ($activeScript && $activeScript->id === $script->id),
                                ])
                            >
                                <p class="text-sm font-medium text-gray-900">{{ $script->name }}</p>
                                <p class="mt-1 text-xs text-gray-500">Status: {{ $script->status?->value ?? 'draft' }} | Steps: {{ $script->steps_count ?? $script->steps()->count() }}</p>
                            </a>
                        @empty
                            <p class="rounded-md border border-dashed border-gray-300 px-3 py-4 text-sm text-gray-500">No call scripts found. Create your first script.</p>
                        @endforelse
                    </div>

                    <div>{{ $scripts->links() }}</div>
                </div>
            </aside>

            <section class="space-y-6 lg:col-span-3">
                <div class="card" x-show="showCreate" x-transition>
                    <div class="card-body">
                        <h2 class="text-lg font-semibold text-gray-900">Create Script</h2>
                        <p class="mt-1 text-sm text-gray-500">Start with one branch-ready step. You can expand via API for larger flows.</p>

                        <form method="POST" action="{{ route('crm.communication.voice.scripts.store') }}" class="mt-4 space-y-5">
                            @csrf
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Script Name <span class="text-red-600">*</span></label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" @class([$fieldClass, $fieldErrorClass => $errors->has('name')]) required>
                                @error('name')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea id="description" name="description" rows="3" @class([$fieldClass, 'resize-y', $fieldErrorClass => $errors->has('description')])>{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                    <select id="status" name="status" @class([$fieldClass, 'pr-10', $fieldErrorClass => $errors->has('status')])>
                                        <option value="draft">Draft</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    @error('status')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <label class="mt-7 inline-flex min-h-11 items-center gap-2 text-sm font-medium text-gray-700">
                                    <input type="checkbox" name="is_default" value="1" class="h-5 w-5 rounded border-2 border-slate-300 bg-white text-indigo-600 focus:ring-2 focus:ring-indigo-500/30">
                                    Set as default script
                                </label>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <h3 class="text-sm font-semibold text-gray-900">Initial Step</h3>
                                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="step_key" class="block text-sm font-medium text-gray-700">Step Key <span class="text-red-600">*</span></label>
                                        <input id="step_key" name="steps[0][step_key]" type="text" value="{{ old('steps.0.step_key', 'intro') }}" @class([$fieldClass, $fieldErrorClass => $errors->has('steps.0.step_key')]) required>
                                        @error('steps.0.step_key')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="step_order" class="block text-sm font-medium text-gray-700">Step Order</label>
                                        <input id="step_order" name="steps[0][step_order]" type="number" min="1" value="{{ old('steps.0.step_order', 1) }}" @class([$fieldClass, $fieldErrorClass => $errors->has('steps.0.step_order')])>
                                        @error('steps.0.step_order')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label for="prompt_text" class="block text-sm font-medium text-gray-700">Prompt Text <span class="text-red-600">*</span></label>
                                    <textarea id="prompt_text" name="steps[0][prompt_text]" rows="3" @class([$fieldClass, 'resize-y', $fieldErrorClass => $errors->has('steps.0.prompt_text')]) required>{{ old('steps.0.prompt_text', 'Thank you for your interest. Are you applying this cycle?') }}</textarea>
                                    @error('steps.0.prompt_text')
                                        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="response_type" class="block text-sm font-medium text-gray-700">Response Type <span class="text-red-600">*</span></label>
                                        <select id="response_type" name="steps[0][response_type]" @class([$fieldClass, 'pr-10', $fieldErrorClass => $errors->has('steps.0.response_type')])>
                                            <option value="text">Text</option>
                                            <option value="number">Number</option>
                                            <option value="boolean">Boolean</option>
                                            <option value="select">Select</option>
                                        </select>
                                        @error('steps.0.response_type')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <label class="mt-7 inline-flex min-h-11 items-center gap-2 text-sm font-medium text-gray-700">
                                        <input type="checkbox" name="steps[0][is_terminal]" value="1" class="h-5 w-5 rounded border-2 border-slate-300 bg-white text-indigo-600 focus:ring-2 focus:ring-indigo-500/30">
                                        Terminal step
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn-primary-sm">Save Script</button>
                        </form>
                    </div>
                </div>

                @if ($activeScript)
                    <div class="card">
                        <div class="card-body">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">{{ $activeScript->name }}</h2>
                                    <p class="text-sm text-gray-500">{{ $activeScript->description ?: 'No description added.' }}</p>
                                </div>
                                <span class="badge badge-blue">{{ $activeScript->status?->value }}</span>
                            </div>

                            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="table-th">Order</th>
                                            <th class="table-th">Key</th>
                                            <th class="table-th">Prompt</th>
                                            <th class="table-th">Type</th>
                                            <th class="table-th">Default Next</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        @foreach ($activeScript->steps as $step)
                                            <tr>
                                                <td class="table-td">{{ $step->step_order }}</td>
                                                <td class="table-td font-medium text-gray-900">{{ $step->step_key }}</td>
                                                <td class="table-td text-gray-700">{{ $step->prompt_text }}</td>
                                                <td class="table-td">{{ $step->response_type?->value }}</td>
                                                <td class="table-td">{{ $step->default_next_step_key ?: 'Terminal' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <form method="POST" action="{{ route('crm.communication.voice.scripts.resolve', $activeScript->uuid) }}" class="mt-6 space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                @csrf
                                <h3 class="text-sm font-semibold text-gray-900">Branch Runner</h3>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="current_step_key" class="block text-sm font-medium text-gray-700">Current Step Key</label>
                                        <input id="current_step_key" name="current_step_key" type="text" value="{{ old('current_step_key', $currentStep?->step_key) }}" @class([$fieldClass, $fieldErrorClass => $errors->has('current_step_key')]) required>
                                        @error('current_step_key')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="response" class="block text-sm font-medium text-gray-700">Sample Response</label>
                                        <input id="response" name="response" type="text" value="{{ old('response', is_scalar($runnerResponse) ? (string) $runnerResponse : '') }}" @class([$fieldClass, $fieldErrorClass => $errors->has('response')])>
                                        @error('response')
                                            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <button type="submit" class="btn-secondary-sm">Resolve Next Step</button>
                            </form>

                            @if ($nextStep)
                                <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4">
                                    <p class="text-sm font-medium text-green-800">Next step resolved: {{ $nextStep->step_key }}</p>
                                    <p class="mt-1 text-sm text-green-700">{{ $nextStep->prompt_text }}</p>
                                </div>
                            @elseif($runnerResponse !== null)
                                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <p class="text-sm font-medium text-amber-800">No next step resolved. This response ends the flow.</p>
                                </div>
                            @endif

                            <div class="mt-6 flex items-center gap-2">
                                <form method="POST" action="{{ route('crm.communication.voice.scripts.destroy', $activeScript->uuid) }}" onsubmit="return confirm('Archive this script?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-secondary-sm">Archive</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts.crm>
