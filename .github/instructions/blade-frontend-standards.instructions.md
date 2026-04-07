---
description: "Use when writing Laravel Blade templates, Blade components, Alpine.js interactions, Livewire components, Tailwind CSS, or any .blade.php file for A2A-CRM. Enforces Blade component structure, Alpine.js for interactivity, Livewire for reactive UI, Tailwind utility classes, layout inheritance, CSRF protection, and XSS-safe output."
applyTo: ["resources/views/**/*.blade.php", "app/View/Components/**/*.php", "app/Livewire/**/*.php"]
---

# A2A-CRM Blade Frontend Standards

## Stack

| Layer | Technology |
|-------|-----------|
| Templates | Laravel Blade (`.blade.php`) |
| Styling | Tailwind CSS v3 |
| Interactivity | Alpine.js v3 (dropdowns, modals, tabs, toggles) |
| Reactive UI | Laravel Livewire v3 (forms, data tables, live search) |
| Charts | Chart.js v4 (via Blade + `<canvas>`) |
| Build | Vite (`vite.config.js`) |

---

## Directory Structure

```
resources/views/
├── layouts/
│   ├── app.blade.php          # Authenticated CRM shell (sidebar, topbar)
│   ├── guest.blade.php        # Unauthenticated (login, student portal)
│   └── portal.blade.php       # Applicant self-service portal
├── components/
│   ├── crm/
│   │   ├── lead-card.blade.php
│   │   ├── lead-timeline.blade.php
│   │   ├── status-badge.blade.php
│   │   ├── ai-suggestion.blade.php   # BRD: CRM-AI-011
│   │   └── kanban-board.blade.php    # BRD: CRM-AP-008
│   └── ui/
│       ├── modal.blade.php
│       ├── data-table.blade.php
│       └── alert.blade.php
├── crm/
│   ├── leads/
│   │   ├── index.blade.php
│   │   ├── show.blade.php
│   │   └── create.blade.php
│   ├── applications/
│   ├── analytics/
│   └── settings/
└── livewire/
    ├── crm/
    │   ├── lead-pipeline.blade.php
    │   ├── lead-search.blade.php
    │   └── analytics-dashboard.blade.php
```

---

## Layout Inheritance

```blade
{{-- resources/views/crm/leads/index.blade.php --}}
<x-layouts.app title="Leads — A2A CRM">

    <x-slot:header>
        <h1 class="text-xl font-semibold text-gray-900">Lead Pipeline</h1>
    </x-slot:header>

    {{-- Livewire component for reactive lead list --}}
    @livewire('crm.lead-pipeline', ['counsellorId' => auth()->id()])

</x-layouts.app>
```

---

## Blade Components (Anonymous + Class-based)

### Anonymous Component (UI primitive)

```blade
{{-- resources/views/components/crm/status-badge.blade.php --}}
@props(['status', 'temperature' => null])

@php
$colours = [
    'HOT'       => 'bg-red-100 text-red-800',
    'WARM'      => 'bg-orange-100 text-orange-800',
    'COLD'      => 'bg-blue-100 text-blue-800',
    'LOST'      => 'bg-gray-100 text-gray-500',
    'CONVERTED' => 'bg-green-100 text-green-800',
];
$colour = $colours[$temperature] ?? 'bg-gray-100 text-gray-700';
@endphp

<span {{ $attributes->class(['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', $colour]) }}>
    {{ $status }}
</span>
```

### Class-Based Component (with logic)

```php
// app/View/Components/CRM/LeadCard.php
final class LeadCard extends Component
{
    public function __construct(
        public readonly Lead $lead,
    ) {}

    public function render(): View
    {
        return view('components.crm.lead-card');
    }
}
```

---

## Alpine.js — Lightweight Interactivity

Use Alpine.js for **client-side-only UI state**: dropdowns, modals, tabs, accordions, toggles.
Do NOT use Alpine.js for data that needs to be persisted or synced with the server — use Livewire.

```blade
{{-- Dropdown menu using Alpine.js --}}
<div x-data="{ open: false }" class="relative">
    <button @click="open = !open" class="btn-secondary">
        Actions <x-heroicon-s-chevron-down class="w-4 h-4 ml-1 inline" />
    </button>

    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10"
    >
        <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit</a>
        <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Mark Lost</a>
    </div>
</div>
```

---

## Livewire v3 — Reactive Server-Driven UI

Use Livewire for: **live search, data tables with filters, real-time lead pipeline, form wizards, dashboard counters.**

```php
// app/Livewire/CRM/LeadPipeline.php
// BRD: CRM-AP-008 — Kanban pipeline board
final class LeadPipeline extends Component
{
    public string $search = '';
    public string $temperatureFilter = '';

    #[Computed]
    public function leads(): LengthAwarePaginator
    {
        return Lead::query()
            ->when($this->search, fn($q) => $q->where('first_name', 'like', "%{$this->search}%"))
            ->when($this->temperatureFilter, fn($q) => $q->where('temperature', $this->temperatureFilter))
            ->with(['counsellor:id,name', 'programmeInterests'])
            ->paginate(25);
    }

    public function render(): View
    {
        return view('livewire.crm.lead-pipeline');
    }
}
```

```blade
{{-- resources/views/livewire/crm/lead-pipeline.blade.php --}}
<div>
    {{-- Search bar — Livewire wire:model for live binding --}}
    <input
        wire:model.live.debounce.300ms="search"
        type="text"
        placeholder="Search leads..."
        class="input-field"
    />

    {{-- Lead cards --}}
    <div class="grid grid-cols-1 gap-4 mt-4">
        @foreach ($this->leads as $lead)
            <x-crm.lead-card :lead="$lead" />
        @endforeach
    </div>

    {{ $this->leads->links() }}

    {{-- Loading state --}}
    <div wire:loading class="text-gray-400 text-sm mt-2">Updating...</div>
</div>
```

---

## Tailwind CSS Conventions

- **Utility classes only** — no custom CSS in `app.css` unless unavoidable
- **No inline `style` attributes** — always Tailwind classes
- **Responsive**: `sm:`, `md:`, `lg:` mobile-first breakpoints
- **Dark mode**: `dark:` prefix where supported
- **Conditional classes**: use Blade `@class` directive

```blade
{{-- ✅ Blade @class directive for conditional Tailwind --}}
<div @class([
    'p-4 rounded-lg border',
    'border-red-300 bg-red-50'   => $lead->temperature === 'HOT',
    'border-blue-300 bg-blue-50' => $lead->temperature === 'COLD',
    'border-gray-200 bg-white'   => !in_array($lead->temperature, ['HOT','COLD']),
])>
```

---

## XSS Safety — Output Escaping

```blade
{{-- ✅ Always use {{ }} for escaped output --}}
{{ $lead->first_name }}

{{-- ❌ Never use {!! !!} with user-supplied data --}}
{!! $lead->notes !!}  {{-- PROHIBITED unless sanitised with strip_tags() or HTMLPurifier --}}

{{-- ✅ If rich HTML is needed, sanitise first --}}
{!! Purify::clean($lead->notes) !!}
```

---

## Forms — CSRF + Validation Errors

```blade
<form action="{{ route('crm.leads.store') }}" method="POST">
    @csrf

    <div>
        <label for="mobile" class="label">Mobile Number</label>
        <input
            id="mobile"
            name="mobile"
            type="tel"
            value="{{ old('mobile') }}"
            @class(['input-field', 'border-red-500' => $errors->has('mobile')])
        />
        @error('mobile')
            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Consent capture — DPDP Act (BRD: CRM-CR-001) --}}
    <div class="flex items-start mt-4">
        <input id="consent_given" name="consent_given" type="checkbox" class="mt-1 mr-2" required />
        <label for="consent_given" class="text-sm text-gray-600">
            I consent to my data being processed for admissions purposes.
        </label>
    </div>
    @error('consent_given')
        <p class="text-red-600 text-sm">{{ $message }}</p>
    @enderror

    <button type="submit" class="btn-primary mt-4">Submit Enquiry</button>
</form>
```

---

## AI Suggestion Component (BRD: CRM-AI-011)

AI output is always a suggestion — human must confirm before action.

```blade
{{-- resources/views/components/crm/ai-suggestion.blade.php --}}
{{-- BRD: CRM-AI-011 — AI output presented as suggestion; human confirms --}}
@props(['suggestion', 'leadUuid'])

<div x-data="{ dismissed: false }" x-show="!dismissed"
     class="border border-indigo-200 bg-indigo-50 rounded-lg p-4 mb-4">

    <div class="flex items-center gap-2 mb-2">
        <x-heroicon-s-sparkles class="w-4 h-4 text-indigo-600" />
        <span class="text-sm font-medium text-indigo-800">AI Suggestion</span>
        <span class="text-xs text-indigo-500 ml-auto">{{ $suggestion->confidence }}% confidence</span>
    </div>

    <p class="text-sm text-gray-800 mb-3">{{ $suggestion->reasoning }}</p>

    <div class="flex gap-2">
        {{-- Accept --}}
        <form action="{{ route('crm.nba.accept', $leadUuid) }}" method="POST">
            @csrf
            <input type="hidden" name="suggestion_id" value="{{ $suggestion->id }}" />
            <button type="submit" class="btn-primary-sm">Accept</button>
        </form>

        {{-- Edit --}}
        <a href="{{ route('crm.leads.show', $leadUuid) }}" class="btn-secondary-sm">Edit</a>

        {{-- Dismiss --}}
        <button @click="dismissed = true" class="btn-ghost-sm">Dismiss</button>
    </div>
</div>
```

---

## Chart.js Integration

```blade
{{-- Admissions funnel chart — BRD: CRM-AR-004 --}}
<canvas id="funnelChart" class="w-full h-64"></canvas>

@push('scripts')
<script>
    const ctx = document.getElementById('funnelChart').getContext('2d')
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($funnel->pluck('stage')),
            datasets: [{
                label: 'Leads',
                data: @json($funnel->pluck('count')),
                backgroundColor: ['#6366f1','#8b5cf6','#a78bfa','#c4b5fd'],
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    })
</script>
@endpush
```

---

## Prohibited Patterns

- ❌ `{!! $userInput !!}` without sanitisation — XSS risk
- ❌ Inline `style` attributes — use Tailwind classes
- ❌ JavaScript `eval()` or `innerHTML` with server data
- ❌ Hardcoded URLs in Blade — always use `route()` or `url()` helpers
- ❌ Business logic in Blade files — use View Composers or Livewire computed properties
- ❌ Storing lead data in `localStorage` — DPDP risk
- ❌ Unescaped route model binding IDs in URLs — always use UUID
- ❌ `@php` blocks with database queries — use Livewire or pass from controller
- ❌ Emojis as icons — use Heroicons v2 SVG only
- ❌ Missing `cursor-pointer` on clickable non-button elements
- ❌ `wire:model.live` without `.debounce` on text inputs
- ❌ Raw hex colors in Blade — use Tailwind semantic tokens

---

## Design Intelligence

For detailed UI/UX guidelines (styles, color palettes, accessibility, animation, chart patterns, pre-delivery checklist), use the `/ui-ux-pro-max` skill. It is pre-adapted for the Laravel Blade + Tailwind CSS + Alpine.js + Livewire v3 stack used in A2A-CRM.

See: [.github/skills/ui-ux-pro-max/SKILL.md](../skills/ui-ux-pro-max/SKILL.md)
