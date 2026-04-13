<x-layouts.crm :title="$questionnaire ? 'Edit Questionnaire' : 'Create Questionnaire'">
    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">{{ $questionnaire ? 'Edit Qualification Questionnaire' : 'Create Qualification Questionnaire' }}</h1>
    </x-slot:header>

    @php
        $defaultQuestions = [
            ['key' => 'budget', 'label' => 'Budget', 'type' => 'text', 'required' => true],
        ];

        $decodedOldQuestions = old('questions_json') ? json_decode((string) old('questions_json'), true) : null;
        $initialQuestions = is_array($decodedOldQuestions)
            ? $decodedOldQuestions
            : ($questionnaire?->questions ?? $defaultQuestions);

        if (! is_array($initialQuestions) || $initialQuestions === []) {
            $initialQuestions = $defaultQuestions;
        }
    @endphp

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <form
            method="POST"
            action="{{ $questionnaire ? route('crm.scoring.questionnaires.update', $questionnaire->uuid) : route('crm.scoring.questionnaires.store') }}"
            class="space-y-6 bg-white border border-gray-200 rounded-lg p-6 shadow-sm"
            x-data="questionnaireBuilder(@js($initialQuestions))"
            x-on:submit="prepareSubmit($event)"
        >
            @csrf
            @if ($questionnaire)
                @method('PUT')
            @endif

            <div>
                <label for="name" class="label">Questionnaire Name</label>
                <input id="name" name="name" type="text" class="input-field" value="{{ old('name', $questionnaire?->name) }}" required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="input-field" required>
                    @foreach (\App\Enums\CRM\QuestionnaireStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $questionnaire?->status?->value ?? 'draft') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="flex items-center justify-between gap-3">
                    <label class="label">Questions</label>
                    <button type="button" class="btn-secondary-sm" @click="addQuestion()">Add Question</button>
                </div>

                <div class="mt-3 space-y-3">
                    <template x-for="(question, index) in questions" :key="index">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-700">Question <span x-text="index + 1"></span></p>
                                <button
                                    type="button"
                                    class="btn-ghost-sm text-red-600"
                                    @click="removeQuestion(index)"
                                    :disabled="questions.length === 1"
                                    aria-label="Remove question"
                                >
                                    Remove
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <div>
                                    <label class="label">Question Key <span class="text-red-500">*</span></label>
                                    <input type="text" class="input-field" x-model="question.key" placeholder="budget_range" @input="syncJson()" required>
                                    <p class="mt-1 text-xs text-gray-500">Use lowercase and underscore style (for example: joining_timeline).</p>
                                </div>

                                <div>
                                    <label class="label">Question Label <span class="text-red-500">*</span></label>
                                    <input type="text" class="input-field" x-model="question.label" placeholder="Budget Range" @input="syncJson()" required>
                                </div>

                                <div>
                                    <label class="label">Type <span class="text-red-500">*</span></label>
                                    <select class="input-field" x-model="question.type" @change="syncJson()" required>
                                        <option value="text">Text</option>
                                        <option value="select">Select</option>
                                        <option value="boolean">Boolean</option>
                                        <option value="number">Number</option>
                                    </select>
                                </div>

                                <div class="flex items-center gap-2 pt-7">
                                    <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" x-model="question.required" @change="syncJson()">
                                    <label class="text-sm font-medium text-gray-700">Required field</label>
                                </div>
                            </div>

                            <div class="mt-3" x-show="question.type === 'select'" x-cloak>
                                <label class="label">Options (comma separated)</label>
                                <input
                                    type="text"
                                    class="input-field"
                                    :value="(question.options || []).join(', ')"
                                    @input="question.options = $event.target.value.split(',').map(o => o.trim()).filter(Boolean); syncJson()"
                                    placeholder="below_2_lakh, 2_to_5_lakh, above_5_lakh"
                                >
                            </div>
                        </div>
                    </template>
                </div>

                <div class="mt-3 rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2">
                    <p class="text-xs text-indigo-700">Tip: Use Add Question for each field. JSON is generated automatically below for compatibility.</p>
                </div>

                <div class="mt-3" x-show="showRawJson" x-cloak>
                    <label class="label">Generated Questions JSON</label>
                    <textarea rows="10" class="input-field font-mono text-sm" x-model="questionsJson" aria-label="Generated questions JSON"></textarea>
                </div>

                <input type="hidden" name="questions_json" x-model="questionsJson">

                <button type="button" class="mt-2 text-xs font-semibold text-indigo-600 hover:text-indigo-800" @click="showRawJson = !showRawJson">
                    <span x-text="showRawJson ? 'Hide raw JSON' : 'Show raw JSON'"></span>
                </button>

                <p class="mt-2 text-xs text-gray-500">You can still review/edit the raw JSON if needed.</p>

                <p class="mt-2 text-sm text-red-600" x-show="validationError" x-text="validationError" role="alert"></p>
                @error('questions')
                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
                @error('questions_json')
                    <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary">{{ $questionnaire ? 'Update Questionnaire' : 'Create Questionnaire' }}</button>
                <a href="{{ route('crm.scoring.questionnaires.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function questionnaireBuilder(initialQuestions) {
            const normalized = Array.isArray(initialQuestions) && initialQuestions.length
                ? initialQuestions.map((q) => ({
                    key: q.key || '',
                    label: q.label || '',
                    type: q.type || 'text',
                    required: Boolean(q.required),
                    options: Array.isArray(q.options) ? q.options : [],
                }))
                : [{ key: '', label: '', type: 'text', required: true, options: [] }];

            return {
                questions: normalized,
                questionsJson: '',
                showRawJson: false,
                validationError: '',

                init() {
                    this.syncJson();
                },

                addQuestion() {
                    this.questions.push({ key: '', label: '', type: 'text', required: false, options: [] });
                    this.syncJson();
                },

                removeQuestion(index) {
                    if (this.questions.length === 1) {
                        return;
                    }
                    this.questions.splice(index, 1);
                    this.syncJson();
                },

                sanitizeQuestions() {
                    return this.questions.map((q) => {
                        const cleaned = {
                            key: String(q.key || '').trim(),
                            label: String(q.label || '').trim(),
                            type: String(q.type || 'text').trim(),
                            required: Boolean(q.required),
                        };

                        if (cleaned.type === 'select') {
                            const options = Array.isArray(q.options) ? q.options.map((o) => String(o).trim()).filter(Boolean) : [];
                            cleaned.options = options;
                        }

                        return cleaned;
                    });
                },

                syncJson() {
                    const sanitized = this.sanitizeQuestions();
                    this.questionsJson = JSON.stringify(sanitized, null, 2);
                },

                prepareSubmit(event) {
                    this.validationError = '';
                    const sanitized = this.sanitizeQuestions();

                    const hasInvalid = sanitized.some((q) => !q.key || !q.label || !q.type);
                    if (hasInvalid) {
                        event.preventDefault();
                        this.validationError = 'Each question must have key, label, and type.';
                        return;
                    }

                    const hasInvalidSelect = sanitized.some((q) => q.type === 'select' && (!Array.isArray(q.options) || q.options.length === 0));
                    if (hasInvalidSelect) {
                        event.preventDefault();
                        this.validationError = 'Select type questions must include at least one option.';
                        return;
                    }

                    this.questionsJson = JSON.stringify(sanitized, null, 2);
                },
            };
        }
    </script>
</x-layouts.crm>
