@php
    $pageTitle = $landingPage?->name ?? 'Create Landing Page';
    $isEditing = $landingPage !== null;
    $statusValue = old('status', $landingPage?->status?->value ?? 'draft');
    $content = old('content', $landingPage?->content ?? []);
    $attribution = old('attribution_params', $landingPage?->attribution_params ?? []);

    $normalizedBlocks = collect($content)->values()->map(function ($block, $index) {
        return [
            'id' => $block['id'] ?? ('block-'.$index),
            'type' => $block['type'] ?? 'value_card',
            'order' => $block['order'] ?? $index,
            'eyebrow' => $block['eyebrow'] ?? '',
            'title' => $block['title'] ?? '',
            'body' => $block['body'] ?? '',
            'metric_label' => $block['metric_label'] ?? '',
            'metric_value' => $block['metric_value'] ?? '',
            'question' => $block['question'] ?? '',
            'answer' => $block['answer'] ?? '',
        ];
    })->all();

    $templateLibrary = [
        [
            'key' => 'placement_booster',
            'name' => 'Placement Booster',
            'description' => 'Outcome-focused cards for employability campaigns.',
            'blocks' => [
                ['id' => 'tpl-placement-1', 'type' => 'value_card', 'order' => 0, 'eyebrow' => 'Career Outcomes', 'title' => 'Industry-ready curriculum', 'body' => 'Build practical skills with mentor-led projects and mock interviews.'],
                ['id' => 'tpl-placement-2', 'type' => 'value_card', 'order' => 1, 'eyebrow' => 'Placement Support', 'title' => 'Dedicated career services', 'body' => 'Access resume clinics, recruiter connect events, and role-fit counselling.'],
                ['id' => 'tpl-placement-3', 'type' => 'value_card', 'order' => 2, 'eyebrow' => 'Employer Network', 'title' => 'Top recruiter ecosystem', 'body' => 'Engage with employer partners across technology, finance, and consulting.'],
            ],
        ],
        [
            'key' => 'scholarship_drive',
            'name' => 'Scholarship Drive',
            'description' => 'Funding-first narrative for high-intent scholarship leads.',
            'blocks' => [
                ['id' => 'tpl-scholarship-1', 'type' => 'value_card', 'order' => 0, 'eyebrow' => 'Financial Support', 'title' => 'Merit scholarship pathways', 'body' => 'Explore tuition support slabs based on academic profile and entrance score.'],
                ['id' => 'tpl-scholarship-2', 'type' => 'stat', 'order' => 1, 'metric_label' => 'Scholarship Seats', 'metric_value' => '120+', 'body' => 'Scholarship evaluations released in monthly cycles.'],
                ['id' => 'tpl-scholarship-3', 'type' => 'faq', 'order' => 2, 'question' => 'Who can apply for scholarship review?', 'answer' => 'Students with qualifying academic records and completed applications can request review.'],
            ],
        ],
    ];
@endphp

<x-layouts.crm>
    <x-slot:header>{{ $pageTitle }}</x-slot:header>

    <x-slot:headerActions>
        <div class="flex items-center gap-3">
            <a href="{{ route('crm.marketing.landing-pages.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500">
                Back
            </a>
            @if($landingPage?->status?->isPubliclyVisible())
                <a href="{{ $landingPage->publicUrl() }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 cursor-pointer focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    Open Public Page
                </a>
            @endif
        </div>
    </x-slot:headerActions>

    @if(session('success'))
    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4" role="alert">
        <p class="text-sm font-semibold text-red-800">Please fix the highlighted fields.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <form method="POST"
              action="{{ $isEditing ? route('crm.marketing.landing-pages.update', $landingPage->uuid) : route('crm.marketing.landing-pages.store') }}"
              class="space-y-6"
              x-data="{
                    blocks: @js($normalizedBlocks),
                    templates: @js($templateLibrary),
                    selectedTemplate: '',
                    draggingIndex: null,
                    syncOrder() {
                        this.blocks = this.blocks.map((block, index) => ({ ...block, order: index }));
                    },
                    addBlock() {
                        if (this.blocks.length >= 6) {
                            return;
                        }

                        this.blocks.push({
                            id: 'block-' + Date.now() + '-' + Math.floor(Math.random() * 1000),
                            type: 'value_card',
                            order: this.blocks.length,
                            eyebrow: '',
                            title: '',
                            body: '',
                            metric_label: '',
                            metric_value: '',
                            question: '',
                            answer: '',
                        });
                        this.syncOrder();
                    },
                    updateType(index, type) {
                        this.blocks[index].type = type;

                        if (type !== 'stat') {
                            this.blocks[index].metric_label = '';
                            this.blocks[index].metric_value = '';
                        }

                        if (type !== 'faq') {
                            this.blocks[index].question = '';
                            this.blocks[index].answer = '';
                        }

                        if (type !== 'value_card') {
                            this.blocks[index].eyebrow = '';
                            this.blocks[index].title = '';
                        }
                    },
                    moveUp(index) {
                        if (index === 0) {
                            return;
                        }

                        [this.blocks[index - 1], this.blocks[index]] = [this.blocks[index], this.blocks[index - 1]];
                        this.syncOrder();
                    },
                    moveDown(index) {
                        if (index >= this.blocks.length - 1) {
                            return;
                        }

                        [this.blocks[index + 1], this.blocks[index]] = [this.blocks[index], this.blocks[index + 1]];
                        this.syncOrder();
                    },
                    removeBlock(index) {
                        this.blocks.splice(index, 1);
                        this.syncOrder();
                    },
                    applyTemplate(templateKey) {
                        const template = this.templates.find((item) => item.key === templateKey);

                        if (!template) {
                            return;
                        }

                        this.blocks = template.blocks.map((block, index) => ({
                            ...block,
                            id: block.id + '-' + Date.now() + '-' + index,
                            order: index,
                        }));
                    },
                    dragStart(index) {
                        this.draggingIndex = index;
                    },
                    dragEnd() {
                        this.draggingIndex = null;
                    },
                    dropAt(index) {
                        if (this.draggingIndex === null || this.draggingIndex === index) {
                            return;
                        }

                        const moved = this.blocks[this.draggingIndex];

                        this.blocks.splice(this.draggingIndex, 1);
                        this.blocks.splice(index, 0, moved);
                        this.syncOrder();
                        this.draggingIndex = null;
                    },
                    reorderWithKeys(event, index) {
                        if (!event.altKey) {
                            return;
                        }

                        if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            this.moveUp(index);
                            return;
                        }

                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            this.moveDown(index);
                        }
                    }
              }">
            @csrf
            @if($isEditing)
                @method('PUT')
            @endif

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900">Page Identity</h2>
                <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">Internal Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $landingPage?->name) }}" maxlength="120"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                               placeholder="MBA 2027 merit scholarship campaign">
                    </div>
                    <div>
                        <label for="slug" class="mb-1.5 block text-sm font-medium text-gray-700">Public Slug</label>
                        <div class="flex items-center overflow-hidden rounded-lg border border-gray-300 bg-gray-50 shadow-sm">
                            <span class="border-r border-gray-300 bg-gray-100 px-3 py-2.5 text-sm text-gray-500">/lp/</span>
                            <input id="slug" name="slug" type="text" value="{{ old('slug', $landingPage?->slug) }}" maxlength="100"
                                   class="block flex-1 bg-transparent px-3 py-2.5 text-sm text-gray-900 focus:outline-none"
                                   placeholder="mba-2027-scholarship">
                        </div>
                    </div>
                    <div>
                        <label for="status" class="mb-1.5 block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                            <option value="draft" @selected($statusValue === 'draft')>Draft</option>
                            <option value="published" @selected($statusValue === 'published')>Published</option>
                            <option value="archived" @selected($statusValue === 'archived')>Archived</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900">Hero Section</h2>
                <div class="mt-5 grid grid-cols-1 gap-5">
                    <div>
                        <label for="headline" class="mb-1.5 block text-sm font-medium text-gray-700">Headline</label>
                        <input id="headline" name="headline" type="text" value="{{ old('headline', $landingPage?->headline) }}" maxlength="180"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                               placeholder="Scholarships, placements, and programme guidance in one page">
                    </div>
                    <div>
                        <label for="subheadline" class="mb-1.5 block text-sm font-medium text-gray-700">Subheadline</label>
                        <textarea id="subheadline" name="subheadline" rows="3" maxlength="320"
                                  class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                  placeholder="Highlight the offer, programme outcome, and next step for the prospect.">{{ old('subheadline', $landingPage?->subheadline) }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="hero_image_url" class="mb-1.5 block text-sm font-medium text-gray-700">Hero Image URL</label>
                            <input id="hero_image_url" name="hero_image_url" type="url" value="{{ old('hero_image_url', $landingPage?->hero_image_url) }}" maxlength="500"
                                   class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                   placeholder="https://images.example.edu/campaign.jpg">
                        </div>
                        <div>
                            <label for="theme_variant" class="mb-1.5 block text-sm font-medium text-gray-700">Theme Variant</label>
                            <select id="theme_variant" name="theme_variant"
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                                <option value="scholar" @selected(old('theme_variant', $landingPage?->theme_variant ?? 'scholar') === 'scholar')>Scholar Blue</option>
                                <option value="sunrise" @selected(old('theme_variant', $landingPage?->theme_variant) === 'sunrise')>Sunrise Gold</option>
                                <option value="forest" @selected(old('theme_variant', $landingPage?->theme_variant) === 'forest')>Forest Green</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Value Sections Builder</h2>
                        <p class="mt-1 text-sm text-gray-500">Compose value cards, stats, and FAQs. Use Alt + Up/Down on a focused block for keyboard reorder.</p>
                    </div>
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-400">Up to 6 cards</span>
                </div>

                <div class="mt-5 grid gap-4 rounded-xl border border-dashed border-indigo-200 bg-indigo-50/40 p-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                    <div>
                        <label for="template_library" class="mb-1.5 block text-sm font-medium text-gray-700">Template Library</label>
                        <select id="template_library"
                                x-model="selectedTemplate"
                                class="block w-full rounded-lg border border-indigo-200 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                            <option value="">Choose a template</option>
                            @foreach($templateLibrary as $template)
                                <option value="{{ $template['key'] }}">{{ $template['name'] }} - {{ $template['description'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button"
                            @click="applyTemplate(selectedTemplate)"
                            class="inline-flex min-h-11 items-center justify-center rounded-lg border border-indigo-200 bg-white px-4 py-2 text-sm font-medium text-indigo-700 shadow-sm transition-colors hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                        Apply Template
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    <template x-for="(block, index) in blocks" :key="block.id">
                        <article class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                                 draggable="true"
                               tabindex="0"
                               role="group"
                               :aria-label="`Content block ${index + 1}`"
                                 @dragstart="dragStart(index)"
                                 @dragend="dragEnd()"
                                 @dragover.prevent
                                 @drop.prevent="dropAt(index)"
                               @keydown="reorderWithKeys($event, index)"
                                 :class="draggingIndex === index ? 'opacity-60 ring-2 ring-indigo-300' : ''">
                            <input type="hidden" :name="`content[${index}][id]`" :value="block.id">
                            <input type="hidden" :name="`content[${index}][type]`" :value="block.type">
                            <input type="hidden" :name="`content[${index}][order]`" :value="index">
                           <input type="hidden" :name="`content[${index}][metric_label]`" :value="block.metric_label">
                           <input type="hidden" :name="`content[${index}][metric_value]`" :value="block.metric_value">
                           <input type="hidden" :name="`content[${index}][question]`" :value="block.question">
                           <input type="hidden" :name="`content[${index}][answer]`" :value="block.answer">

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 6h.01M8 12h.01M8 18h.01M16 6h.01M16 12h.01M16 18h.01" />
                                    </svg>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="`Card ${index + 1}`"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <select :value="block.type"
                                            @change="updateType(index, $event.target.value)"
                                            class="min-h-11 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                                            :aria-label="`Block ${index + 1} type`">
                                        <option value="value_card">Value Card</option>
                                        <option value="stat">Stat</option>
                                        <option value="faq">FAQ</option>
                                    </select>
                                    <button type="button"
                                            @click="moveUp(index)"
                                            class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                        Move Up
                                    </button>
                                    <button type="button"
                                            @click="moveDown(index)"
                                            class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                                        Move Down
                                    </button>
                                    <button type="button"
                                            @click="removeBlock(index)"
                                            class="inline-flex min-h-11 items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 transition-colors hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 cursor-pointer">
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-4">
                                <template x-if="block.type === 'value_card'">
                                 <div class="grid grid-cols-1 gap-4">
                                     <input type="text"
                                         :name="`content[${index}][eyebrow]`"
                                         x-model="block.eyebrow"
                                         class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                         placeholder="Outcome, scholarship, placement">
                                     <input type="text"
                                         :name="`content[${index}][title]`"
                                         x-model="block.title"
                                         class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                         placeholder="Title">
                                     <textarea :name="`content[${index}][body]`"
                                         x-model="block.body"
                                         rows="3"
                                         class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                         placeholder="Short supporting message"></textarea>
                                 </div>
                                </template>

                                <template x-if="block.type === 'stat'">
                                 <div class="grid grid-cols-1 gap-4">
                                     <input type="text"
                                         :name="`content[${index}][metric_label]`"
                                         x-model="block.metric_label"
                                         class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                         placeholder="Metric label (example: Placement Support)">
                                     <input type="text"
                                         :name="`content[${index}][metric_value]`"
                                         x-model="block.metric_value"
                                         class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                         placeholder="Metric value (example: 94%)">
                                     <textarea :name="`content[${index}][body]`"
                                         x-model="block.body"
                                         rows="2"
                                         class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                         placeholder="Optional metric note"></textarea>
                                 </div>
                                </template>

                                <template x-if="block.type === 'faq'">
                                 <div class="grid grid-cols-1 gap-4">
                                     <input type="text"
                                         :name="`content[${index}][question]`"
                                         x-model="block.question"
                                         class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                         placeholder="FAQ question">
                                     <textarea :name="`content[${index}][answer]`"
                                         x-model="block.answer"
                                         rows="3"
                                         class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                         placeholder="FAQ answer"></textarea>
                                 </div>
                                </template>
                            </div>
                        </article>
                    </template>

                    <div x-show="blocks.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500">
                        No cards yet. Add a card or apply a template to start building.
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <button type="button"
                                @click="addBlock()"
                                class="inline-flex min-h-11 items-center rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 transition-colors hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                            Add Card
                        </button>
                        <p class="text-xs text-gray-500">Card count: <span x-text="blocks.length"></span>/6</p>
                    </div>
                </div>

                <input type="hidden" name="content_json" :value="JSON.stringify(blocks)">
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900">Lead Capture and Attribution</h2>
                <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="web_form_id" class="mb-1.5 block text-sm font-medium text-gray-700">Linked Web Form</label>
                        <select id="web_form_id" name="web_form_id"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer">
                            <option value="">No form linked yet</option>
                            @foreach($webForms as $webForm)
                                <option value="{{ $webForm->id }}" @selected((string) old('web_form_id', $landingPage?->web_form_id) === (string) $webForm->id)>
                                    {{ $webForm->name }} ({{ $webForm->slug }})
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">The landing page reuses your existing public web form inside an embedded section.</p>
                    </div>
                    <div>
                        <label for="cta_label" class="mb-1.5 block text-sm font-medium text-gray-700">Primary CTA Label</label>
                        <input id="cta_label" name="cta_label" type="text" value="{{ old('cta_label', $landingPage?->cta_label ?? 'Submit enquiry') }}" maxlength="60"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="cta_secondary_label" class="mb-1.5 block text-sm font-medium text-gray-700">Secondary CTA Label</label>
                        <input id="cta_secondary_label" name="cta_secondary_label" type="text" value="{{ old('cta_secondary_label', $landingPage?->cta_secondary_label) }}" maxlength="60"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                               placeholder="View brochure">
                    </div>
                    <div>
                        <label for="utm_source" class="mb-1.5 block text-sm font-medium text-gray-700">UTM Source</label>
                        <input id="utm_source" name="attribution_params[utm_source]" type="text" value="{{ $attribution['utm_source'] ?? '' }}" maxlength="120"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                               placeholder="meta_ads">
                    </div>
                    <div>
                        <label for="utm_medium" class="mb-1.5 block text-sm font-medium text-gray-700">UTM Medium</label>
                        <input id="utm_medium" name="attribution_params[utm_medium]" type="text" value="{{ $attribution['utm_medium'] ?? '' }}" maxlength="120"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                               placeholder="paid_social">
                    </div>
                    <div>
                        <label for="utm_campaign" class="mb-1.5 block text-sm font-medium text-gray-700">UTM Campaign</label>
                        <input id="utm_campaign" name="attribution_params[utm_campaign]" type="text" value="{{ $attribution['utm_campaign'] ?? '' }}" maxlength="120"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                               placeholder="mba-2027-scholarship">
                    </div>
                    <div>
                        <label for="utm_content" class="mb-1.5 block text-sm font-medium text-gray-700">UTM Content</label>
                        <input id="utm_content" name="attribution_params[utm_content]" type="text" value="{{ $attribution['utm_content'] ?? '' }}" maxlength="120"
                               class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                               placeholder="hero-banner-a">
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900">SEO Metadata</h2>
                <div class="mt-5 grid grid-cols-1 gap-5">
                    <input id="seo_title" name="seo_title" type="text" value="{{ old('seo_title', $landingPage?->seo_title) }}" maxlength="160"
                           class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                           placeholder="Page title for search and sharing">
                    <textarea id="seo_description" name="seo_description" rows="3" maxlength="320"
                              class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                              placeholder="Short summary for previews and meta description">{{ old('seo_description', $landingPage?->seo_description) }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                    {{ $isEditing ? 'Save Changes' : 'Create Landing Page' }}
                </button>
            </div>
        </form>

        <aside class="space-y-6">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Launch Summary</h2>
                </div>
                <div class="space-y-4 px-5 py-5 text-sm text-gray-600">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Public URL</p>
                        <p class="mt-1 break-all font-medium text-gray-900">{{ $landingPage?->publicUrl() ?? url('/lp/{slug}') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Linked Form</p>
                        <p class="mt-1 font-medium text-gray-900">{{ $landingPage?->webForm?->name ?? 'No form attached yet' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Current Status</p>
                        <p class="mt-1 font-medium text-gray-900">{{ ucfirst($statusValue) }}</p>
                    </div>
                    @if($landingPage)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Views (All Time)</p>
                            <p class="mt-1 font-medium text-gray-900">{{ number_format((int) ($landingPage->landing_page_views_count ?? 0)) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Views (Last 7 Days)</p>
                            <p class="mt-1 font-medium text-gray-900">{{ number_format((int) ($landingPage->view_count_last_7d ?? 0)) }}</p>
                        </div>
                    @endif
                    @if($landingPage?->published_at)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Published At</p>
                            <p class="mt-1 font-medium text-gray-900">{{ $landingPage->published_at->format('d M Y, h:i A') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Preview Notes</h2>
                </div>
                <div class="space-y-3 px-5 py-5 text-sm leading-relaxed text-gray-600">
                    <p>The public page uses your selected theme variant and renders the linked CRM web form in an embedded section.</p>
                    <p>Any UTM parameters configured here are appended to the embed URL so existing lead-capture UTM handling continues to work without duplicating the form stack.</p>
                    <p>Publish only after a web form is attached and the consent copy on that form has been reviewed.</p>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.crm>