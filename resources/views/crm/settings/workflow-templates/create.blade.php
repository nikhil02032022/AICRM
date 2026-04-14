<x-layouts.crm title="New Workflow Template">
    <div class="max-w-2xl space-y-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('crm.settings.workflow-templates.index') }}" class="text-gray-400 hover:text-gray-600" aria-label="Back">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-2xl font-bold text-gray-900">New Workflow Template</h1>
        </div>

        <form method="POST" action="{{ route('crm.settings.workflow-templates.store') }}" class="space-y-6">
            @csrf

            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Template Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        placeholder="e.g. Lead Nurture — Engineering Programmes">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" name="description" rows="2"
                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                        <select id="category" name="category" required
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(\App\Enums\CRM\WorkflowTemplateCategory::cases() as $cat)
                            <option value="{{ $cat->value }}" {{ old('category') === $cat->value ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $cat->value)) }}
                            </option>
                            @endforeach
                        </select>
                        @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="trigger_type" class="block text-sm font-medium text-gray-700">Trigger Type <span class="text-red-500">*</span></label>
                        <input type="text" id="trigger_type" name="trigger_type" value="{{ old('trigger_type') }}" required
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="e.g. lead_status_changed">
                        @error('trigger_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input id="is_global" type="checkbox" name="is_global" value="1" {{ old('is_global') ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="is_global" class="text-sm text-gray-700">Global template (available to all institutions)</label>
                </div>
            </div>

            {{-- Template data JSON editor --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm p-6 space-y-3">
                <div>
                    <label for="template_data" class="block text-sm font-medium text-gray-700">
                        Template Data (JSON)  <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-400 mt-0.5">Define the workflow steps, conditions, and actions as a JSON structure.</p>
                </div>
                <textarea
                    id="template_data"
                    name="template_data"
                    rows="14"
                    required
                    spellcheck="false"
                    class="mt-1 block w-full rounded-lg border-gray-300 font-mono text-xs bg-gray-50 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder='{
  "steps": [
    {
      "type": "send_email",
      "delay_hours": 0,
      "template_id": null
    }
  ]
}'
                >{{ old('template_data', '') }}</textarea>
                @error('template_data') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('crm.settings.workflow-templates.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary">Save Template</button>
            </div>
        </form>
    </div>
</x-layouts.crm>
