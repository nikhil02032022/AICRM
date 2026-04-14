@php
    $isEdit = isset($template);
    $pageTitle = $isEdit ? 'Edit Workflow Template' : 'New Workflow Template';
    $formAction = $isEdit
        ? route('crm.settings.workflow-templates.update', $template->uuid)
        : route('crm.settings.workflow-templates.store');
    $initialSteps = old('template_data.steps', $template->template_data['steps'] ?? [
        ['type' => 'send_email', 'delay_hours' => 0, 'template_id' => null],
    ]);
    $initialConfig = old('template_data.config', $template->template_data['config'] ?? [
        'stop_on_reply' => false,
        'exit_on_conversion' => true,
    ]);
@endphp

<x-layouts.crm :title="$pageTitle">
    <div class="max-w-3xl space-y-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('crm.settings.workflow-templates.index') }}"
               class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition-colors duration-150 hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
               aria-label="Back to workflow templates">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold leading-tight text-gray-900">{{ $pageTitle }}</h1>
                <p class="mt-0.5 text-sm text-gray-500">Set up the workflow using simple fields. JSON is generated automatically.</p>
            </div>
        </div>

        <form
            method="POST"
            action="{{ $formAction }}"
            class="space-y-6"
            x-data="{
                formError: '',
                steps: @js($initialSteps),
                config: @js($initialConfig),
                defaultStep() {
                    return {
                        type: 'send_email',
                        delay_hours: 0,
                        template_id: '',
                        channel: '',
                        message: '',
                        status: '',
                        task_title: '',
                        assignee_id: '',
                        condition_field: '',
                        condition_operator: 'equals',
                        condition_value: ''
                    };
                },
                addStep() {
                    this.steps.push(this.defaultStep());
                    this.formError = '';
                },
                removeStep(index) {
                    this.steps.splice(index, 1);
                    if (this.steps.length === 0) {
                        this.formError = 'Add at least one step before saving.';
                    }
                },
                shouldShowTemplateId(step) {
                    return ['send_email', 'send_sms', 'send_whatsapp'].includes(step.type);
                },
                stepLabel(step) {
                    return step.type.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
                },
                jsonPreview() {
                    return JSON.stringify({ steps: this.steps, config: this.config }, null, 2);
                }
            }"
            @submit="if (steps.length === 0) { formError = 'Add at least one step before saving.'; $event.preventDefault(); }"
        >
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-5">
                <div>
                    <label for="name" class="label">Template Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name', $template->name ?? '') }}" required
                        @class(['input-field', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('name')])
                        placeholder="e.g. Lead Nurture — Engineering Programmes">
                    @error('name') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="label">Description</label>
                    <textarea id="description" name="description" rows="2"
                        @class(['input-field resize-y', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('description')])>{{ old('description', $template->description ?? '') }}</textarea>
                    @error('description') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="category" class="label">Category <span class="text-red-500">*</span></label>
                        <select id="category" name="category" required
                            @class(['input-field cursor-pointer', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('category')])>
                            @foreach(\App\Enums\CRM\WorkflowTemplateCategory::cases() as $cat)
                            <option value="{{ $cat->value }}" {{ old('category', $template->category->value ?? '') === $cat->value ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $cat->value)) }}
                            </option>
                            @endforeach
                        </select>
                        @error('category') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="trigger_type" class="label">Trigger Type <span class="text-red-500">*</span></label>
                        <input type="text" id="trigger_type" name="trigger_type" value="{{ old('trigger_type', $template->trigger_type ?? '') }}" required
                            @class(['input-field', 'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('trigger_type')])
                            placeholder="e.g. lead_status_changed">
                        @error('trigger_type') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3" @if($isEdit) style="display:none" @endif>
                    <input id="is_global" type="checkbox" name="is_global" value="1" {{ old('is_global', $template->is_global ?? false) ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="is_global" class="text-sm text-gray-700">Global template (available to all institutions)</label>
                </div>
            </div>

            {{-- Non-technical workflow builder (posts template_data as structured array) --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm space-y-4">
                <div>
                    <p class="label mb-0">Workflow Steps <span class="text-red-500">*</span></p>
                    <p class="mt-0.5 text-xs text-gray-500">Add one or more steps. The system will generate JSON automatically.</p>
                </div>

                <template x-for="(step, index) in steps" :key="index">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-800">
                                Step <span x-text="index + 1"></span>:
                                <span x-text="stepLabel(step)"></span>
                            </p>
                            <button
                                type="button"
                                class="text-xs font-medium text-red-600 hover:text-red-700"
                                @click="removeStep(index)"
                            >
                                Remove
                            </button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label">Step Type</label>
                                <select x-model="step.type" class="input-field cursor-pointer">
                                    <option value="send_email">Send Email</option>
                                    <option value="send_sms">Send SMS</option>
                                    <option value="send_whatsapp">Send WhatsApp</option>
                                    <option value="create_task">Create Task</option>
                                    <option value="update_status">Update Lead Status</option>
                                    <option value="condition_check">Condition Check</option>
                                    <option value="wait">Wait</option>
                                </select>
                            </div>

                            <div>
                                <label class="label">Delay (Hours)</label>
                                <input x-model.number="step.delay_hours" type="number" min="0" class="input-field" placeholder="0">
                            </div>
                        </div>

                        <div x-show="shouldShowTemplateId(step)">
                            <label class="label">Message Template ID</label>
                            <input x-model="step.template_id" type="text" class="input-field" placeholder="e.g. email_template_welcome">
                        </div>

                        <div x-show="step.type === 'create_task'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label">Task Title</label>
                                <input x-model="step.task_title" type="text" class="input-field" placeholder="Call lead for counselling">
                            </div>
                            <div>
                                <label class="label">Assign To (User ID)</label>
                                <input x-model="step.assignee_id" type="number" min="1" class="input-field" placeholder="Optional">
                            </div>
                        </div>

                        <div x-show="step.type === 'update_status'">
                            <label class="label">Target Lead Status</label>
                            <input x-model="step.status" type="text" class="input-field" placeholder="e.g. counselling_scheduled">
                        </div>

                        <div x-show="step.type === 'condition_check'" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label class="label">Field</label>
                                <input x-model="step.condition_field" type="text" class="input-field" placeholder="lead_score">
                            </div>
                            <div>
                                <label class="label">Operator</label>
                                <select x-model="step.condition_operator" class="input-field cursor-pointer">
                                    <option value="equals">Equals</option>
                                    <option value="not_equals">Not Equals</option>
                                    <option value="greater_than">Greater Than</option>
                                    <option value="less_than">Less Than</option>
                                    <option value="contains">Contains</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Value</label>
                                <input x-model="step.condition_value" type="text" class="input-field" placeholder="80">
                            </div>
                        </div>

                        <input type="hidden" :name="`template_data[steps][${index}][type]`" :value="step.type">
                        <input type="hidden" :name="`template_data[steps][${index}][delay_hours]`" :value="step.delay_hours ?? 0">
                        <input type="hidden" :name="`template_data[steps][${index}][template_id]`" :value="step.template_id ?? ''">
                        <input type="hidden" :name="`template_data[steps][${index}][task_title]`" :value="step.task_title ?? ''">
                        <input type="hidden" :name="`template_data[steps][${index}][assignee_id]`" :value="step.assignee_id ?? ''">
                        <input type="hidden" :name="`template_data[steps][${index}][status]`" :value="step.status ?? ''">
                        <input type="hidden" :name="`template_data[steps][${index}][condition_field]`" :value="step.condition_field ?? ''">
                        <input type="hidden" :name="`template_data[steps][${index}][condition_operator]`" :value="step.condition_operator ?? ''">
                        <input type="hidden" :name="`template_data[steps][${index}][condition_value]`" :value="step.condition_value ?? ''">
                    </div>
                </template>

                <div class="flex items-center justify-between gap-3">
                    <button type="button" class="btn-secondary" @click="addStep()">Add Step</button>
                    <p class="text-xs text-gray-500">Total Steps: <span class="font-semibold" x-text="steps.length"></span></p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4 space-y-3">
                    <p class="text-sm font-semibold text-gray-800">Workflow Settings</p>

                    <div class="flex items-center gap-3">
                        <input id="stop_on_reply" type="checkbox" x-model="config.stop_on_reply" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="stop_on_reply" class="text-sm text-gray-700">Stop workflow when lead replies</label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input id="exit_on_conversion" type="checkbox" x-model="config.exit_on_conversion" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="exit_on_conversion" class="text-sm text-gray-700">Exit workflow when lead converts</label>
                    </div>

                    <input type="hidden" name="template_data[config][stop_on_reply]" :value="config.stop_on_reply ? 1 : 0">
                    <input type="hidden" name="template_data[config][exit_on_conversion]" :value="config.exit_on_conversion ? 1 : 0">
                </div>

                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-medium text-gray-600">Auto-generated JSON Preview</p>
                    <pre class="mt-2 max-h-56 overflow-auto rounded-md bg-white p-3 text-xs text-gray-700" x-text="jsonPreview()"></pre>
                </div>

                <p x-show="formError" x-text="formError" class="text-sm text-red-600" role="alert"></p>
                @error('template_data') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('crm.settings.workflow-templates.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">{{ $isEdit ? 'Update Template' : 'Save Template' }}</button>
            </div>
        </form>
    </div>
</x-layouts.crm>
