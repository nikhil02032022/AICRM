<x-layouts.crm>
    <x-slot:header>{{ $workflow ? 'Edit Workflow' : 'New Workflow' }}</x-slot:header>

    <x-slot:headerActions>
        <a href="{{ route('crm.marketing.automation-workflows.index') }}"
           class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            Back to list
        </a>
    </x-slot:headerActions>

    <form method="POST"
          action="{{ $workflow ? route('crm.marketing.automation-workflows.update', $workflow->uuid) : route('crm.marketing.automation-workflows.store') }}"
          x-data="automationBuilder({
                steps: @js($workflow?->steps?->map(fn($step) => [
                    'id' => $step->uuid,
                    'order' => (int) $step->step_order,
                    'node_type' => $step->node_type?->value,
                    'name' => $step->name,
                    'config' => $step->config,
                    'delay_minutes' => $step->delay_minutes,
                ])->values() ?? []),
            })"
          @submit="prepareSubmit"
          class="space-y-6">
        @csrf
        @if($workflow)
            @method('PUT')
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">Workflow Identity</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">Workflow Name <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" required value="{{ old('name', $workflow?->name) }}"
                           class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('description', $workflow?->description) }}</textarea>
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status"
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        @php($selectedStatus = old('status', $workflow?->status?->value ?? 'draft'))
                        <option value="draft" @selected($selectedStatus === 'draft')>Draft</option>
                        <option value="active" @selected($selectedStatus === 'active')>Active</option>
                        <option value="paused" @selected($selectedStatus === 'paused')>Paused</option>
                        <option value="archived" @selected($selectedStatus === 'archived')>Archived</option>
                    </select>
                </div>

                <div>
                    <label for="trigger_type" class="block text-sm font-medium text-gray-700">Trigger Type <span class="text-red-500">*</span></label>
                    <select id="trigger_type" name="trigger_type" required
                            class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        @php($selectedTrigger = old('trigger_type', $workflow?->trigger_type ?? 'lead_created'))
                        <option value="lead_created" @selected($selectedTrigger === 'lead_created')>Lead Created</option>
                        <option value="form_submitted" @selected($selectedTrigger === 'form_submitted')>Form Submitted</option>
                        <option value="email_opened" @selected($selectedTrigger === 'email_opened')>Email Opened</option>
                        <option value="link_clicked" @selected($selectedTrigger === 'link_clicked')>Link Clicked</option>
                        <option value="lead_score_changed" @selected($selectedTrigger === 'lead_score_changed')>Lead Score Changed</option>
                        <option value="status_changed" @selected($selectedTrigger === 'status_changed')>Status Changed</option>
                        <option value="event_based" @selected($selectedTrigger === 'event_based')>Event Based (Open Day / Webinar / Deadline)</option>
                        <option value="date_time_based" @selected($selectedTrigger === 'date_time_based')>Date / Time Based</option>
                        <option value="inactivity_timeout" @selected($selectedTrigger === 'inactivity_timeout')>Inactivity Timeout</option>
                        <option value="re_engagement" @selected($selectedTrigger === 're_engagement')>Re-engagement (Cold / Inactive)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Workflow Steps</h2>
                    <p class="text-sm text-gray-500">Add trigger, condition, and action nodes and reorder them as needed.</p>
                </div>
                <button type="button"
                        @click="addStep()"
                        class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Add Step
                </button>
            </div>

            <template x-if="steps.length === 0">
                <p class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-sm text-gray-500">No steps yet. Add your first step.</p>
            </template>

            <div class="mt-4 space-y-3">
                <template x-for="(step, index) in steps" :key="step.id">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="grid gap-3 md:grid-cols-12 md:items-end">
                            <div class="md:col-span-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Type</label>
                                <select x-model="step.node_type"
                                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                                    <option value="trigger">Trigger</option>
                                    <option value="condition">Condition</option>
                                    <option value="action">Action</option>
                                </select>
                            </div>
                            <div class="md:col-span-4">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Name</label>
                                <input type="text" x-model="step.name"
                                       class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Delay (minutes)</label>
                                <input type="number" min="0" x-model="step.delay_minutes"
                                       class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                            </div>
                            <div class="md:col-span-2">
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="moveUp(index)" :disabled="index === 0"
                                            class="inline-flex min-h-10 items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 disabled:opacity-40">
                                        Up
                                    </button>
                                    <button type="button" @click="moveDown(index)" :disabled="index === steps.length - 1"
                                            class="inline-flex min-h-10 items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 disabled:opacity-40">
                                        Down
                                    </button>
                                    <button type="button" @click="removeStep(index)"
                                            class="inline-flex min-h-10 items-center rounded-md bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <input type="hidden" name="steps_json" :value="serializedSteps">
            @error('steps')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('crm.marketing.automation-workflows.index') }}"
               class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex min-h-11 items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ $workflow ? 'Save Changes' : 'Create Workflow' }}
            </button>
        </div>
    </form>

    <script>
        function automationBuilder(initialState) {
            return {
                steps: Array.isArray(initialState.steps) ? initialState.steps : [],
                get serializedSteps() {
                    return JSON.stringify(this.steps.map((step, index) => ({
                        id: step.id || ('step-' + index),
                        order: index,
                        node_type: step.node_type || 'action',
                        name: step.name || ('Step ' + (index + 1)),
                        config: step.config || null,
                        delay_minutes: step.delay_minutes === '' || step.delay_minutes === null ? null : Number(step.delay_minutes),
                    })));
                },
                addStep() {
                    this.steps.push({
                        id: 'step-' + Date.now() + '-' + this.steps.length,
                        order: this.steps.length,
                        node_type: 'action',
                        name: 'Step ' + (this.steps.length + 1),
                        config: null,
                        delay_minutes: null,
                    });
                },
                removeStep(index) {
                    this.steps.splice(index, 1);
                },
                moveUp(index) {
                    if (index < 1) {
                        return;
                    }

                    const item = this.steps[index];
                    this.steps.splice(index, 1);
                    this.steps.splice(index - 1, 0, item);
                },
                moveDown(index) {
                    if (index >= this.steps.length - 1) {
                        return;
                    }

                    const item = this.steps[index];
                    this.steps.splice(index, 1);
                    this.steps.splice(index + 1, 0, item);
                },
                prepareSubmit() {
                    if (this.steps.length === 0) {
                        this.addStep();
                    }
                },
            };
        }
    </script>
</x-layouts.crm>
