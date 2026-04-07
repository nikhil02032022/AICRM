---
name: ui-ux-pro-max
description: "UI/UX design intelligence for A2A-CRM. Comprehensive design guide covering 67 styles, 161 color palettes, 57 font pairings, 99 UX guidelines, and 25 chart types — adapted for the Laravel Blade + Tailwind CSS + Alpine.js + Livewire v3 stack. Actions: plan, build, create, design, implement, review, fix, improve, optimize, enhance, refactor, and check UI/UX code. Elements: button, modal, navbar, sidebar, card, table, form, data table, chart, Livewire component. Styles: glassmorphism, minimalism, brutalism, neumorphism, bento grid, dark mode, flat design. Topics: color systems, accessibility, animation, layout, typography, spacing, interaction states, WCAG, DPDP-safe UI."
---

# UI/UX Pro Max — A2A-CRM Design Intelligence

Comprehensive design guide for A2A-CRM (Laravel Blade + Tailwind CSS + Alpine.js + Livewire v3).
Source: [nextlevelbuilder/ui-ux-pro-max-skill](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) — adapted for this project's stack.

---

## When to Apply

**Use this skill for any task that changes how a feature looks, feels, moves, or is interacted with.**

### Must Use
- Designing new Blade views (dashboard, data table, lead pipeline, form, analytics)
- Creating or refactoring Blade components (`resources/views/components/`)
- Choosing Tailwind color schemes, typography, or spacing conventions
- Writing Alpine.js interaction patterns (modals, dropdowns, toggles)
- Reviewing Blade/Livewire code for accessibility or visual quality
- Implementing responsive layouts, animations, or Livewire reactive UI
- Making product-level design decisions for A2A-CRM screens

### Skip
- Pure backend logic, API design, or database migrations
- Performance work unrelated to the interface
- Queue/job, service, or repository layer work

---

## Rule Categories by Priority

| Priority | Category | Impact | Key Checks | Anti-Patterns |
|----------|----------|--------|------------|---------------|
| 1 | Accessibility | CRITICAL | Contrast 4.5:1, alt text, keyboard nav, aria-labels | Removing focus rings, icon-only buttons without labels |
| 2 | Touch & Interaction | CRITICAL | Min click target 44×44px, loading feedback, hover states | Reliance on hover only, 0ms state changes |
| 3 | Performance | HIGH | WebP/AVIF, lazy loading, CLS < 0.1, Livewire debounce | Eager-loading all Livewire, layout thrashing |
| 4 | Style Selection | HIGH | Match style to product type, SVG icons only | Mixing flat & skeuomorphic, emojis as icons |
| 5 | Layout & Responsive | HIGH | Mobile-first breakpoints, viewport meta, no horizontal scroll | Fixed px widths, disable zoom |
| 6 | Typography & Color | MEDIUM | Base 16px, line-height 1.5, semantic Tailwind tokens | Text < 12px, gray-on-gray, raw hex in components |
| 7 | Animation | MEDIUM | Duration 150–300ms, transform/opacity only | Width/height animation, no reduced-motion support |
| 8 | Forms & Feedback | MEDIUM | Visible labels, error near field, submit states | Placeholder-only label, errors at top only |
| 9 | Navigation Patterns | HIGH | Predictable back, sidebar ≤ 5 primary items, deep linking | Overloaded nav, broken back behavior |
| 10 | Charts & Data | LOW | Legends, tooltips, accessible colors, Chart.js canvas | Color-only data meaning, empty axis on error |

---

## Quick Reference

### 1. Accessibility (CRITICAL)

- `color-contrast` — Minimum 4.5:1 for normal text; 3:1 for large text (WCAG AA)
- `focus-states` — Visible focus rings on all interactive elements (Tailwind `focus:ring-2 focus:ring-indigo-500`)
- `alt-text` — Descriptive alt text for meaningful images; `aria-hidden="true"` for decorative
- `aria-labels` — `aria-label` for icon-only buttons; never rely on icon alone
- `keyboard-nav` — Tab order matches visual order; modals trap focus, restore on close
- `form-labels` — Always `<label for="...">` — never placeholder-only
- `skip-links` — Skip to main content for keyboard users
- `heading-hierarchy` — Sequential h1→h6, no level skip
- `color-not-only` — Don't convey meaning by color alone; add icon or text
- `reduced-motion` — Respect `prefers-reduced-motion`; wrap Alpine.js transitions: `x-transition` respects CSS `@media (prefers-reduced-motion)`

**Blade pattern:**
```blade
<button
    type="button"
    aria-label="Close modal"
    class="p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
    @click="open = false"
>
    <x-heroicon-s-x-mark class="w-5 h-5" />
</button>
```

---

### 2. Touch & Interaction (CRITICAL)

- `touch-target-size` — Minimum 44×44px for all clickable elements
- `cursor-pointer` — Add `cursor-pointer` to all clickable non-button elements
- `hover-states` — All interactive elements must have `hover:` Tailwind variants (150–300ms transition)
- `loading-buttons` — Disable buttons during Livewire `wire:loading`; show spinner
- `error-feedback` — Clear error messages near the problem field (not only at top)
- `state-clarity` — Hover / active / focus / disabled states must be visually distinct

**Livewire loading pattern:**
```blade
<button
    wire:click="save"
    wire:loading.attr="disabled"
    class="btn-primary cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
>
    <span wire:loading.remove>Save</span>
    <span wire:loading class="flex items-center gap-2">
        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">...</svg>
        Saving...
    </span>
</button>
```

---

### 3. Performance (HIGH)

- `livewire-debounce` — `wire:model.live.debounce.300ms` for search inputs; never `wire:model.live` on every keypress
- `lazy-livewire` — `@livewire('component', lazy: true)` for below-fold components
- `image-webp` — Use WebP/AVIF via Vite asset pipeline; declare `width`/`height` to prevent CLS
- `alpine-init` — Avoid heavy computation in `x-init`; move to Livewire computed properties
- `n-plus-one` — All Livewire computed properties must `with()` eager load; check Telescope in dev
- `vite-chunks` — Ensure Chart.js is loaded only on analytics pages via `@push('scripts')`
- `lazy-load-images` — `loading="lazy"` for all non-hero images

---

### 4. Style Selection (HIGH)

**A2A-CRM Recommended Style: Clean Professional / Minimal SaaS**

| Attribute | Value |
|-----------|-------|
| UI Style | Minimal SaaS + Subtle Glassmorphism accents |
| Primary Color | Indigo (`#6366f1`) |
| Secondary | Violet (`#7c3aed`) |
| CTA | Indigo-600 (`#4f46e5`) |
| Background | Gray-50 (`#f9fafb`) |
| Card Surface | White with `shadow-sm` |
| Text (primary) | Gray-900 (`#111827`) |
| Text (secondary) | Gray-600 (`#4b5563`) |
| Error | Red-600 (`#dc2626`) |
| Success | Green-600 (`#16a34a`) |
| Warning | Amber-600 (`#d97706`) |
| Typography | Inter (headings + body) |

- `no-emoji-icons` — Use SVG icons (Heroicons v2, Lucide); never emojis in navigation or UI
- `consistency` — Same style across all CRM views; no mixing dark + light card variants on same page
- `color-semantic-tokens` — Always Tailwind semantic classes (`text-gray-900`, `bg-indigo-600`); no raw hex in `.blade.php`

---

### 5. Layout & Responsive (HIGH)

- `viewport-meta` — `<meta name="viewport" content="width=device-width, initial-scale=1">` in app layout
- `mobile-first` — Design at 375px, then tablet (768px), desktop (1024px), wide (1440px)
- `breakpoint-consistency` — `sm:` / `md:` / `lg:` / `xl:` Tailwind breakpoints only; no custom CSS breakpoints
- `readable-font-size` — Minimum `text-base` (16px) for body text
- `line-length` — `max-w-prose` or `max-w-2xl` for long-form content
- `horizontal-scroll` — Never allow horizontal scroll; test with browser DevTools mobile view
- `spacing-scale` — Use Tailwind 4/8pt spacing scale: `p-2`, `p-4`, `p-6`, `p-8`, `gap-4`, `gap-6`
- `container-width` — `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8` for page containers
- `sidebar-offset` — Main content must account for fixed sidebar width (`lg:pl-64`)
- `z-index-scale` — Define in `tailwind.config.js`: modal=50, sidebar=40, topbar=30, dropdown=20

---

### 6. Typography & Color (MEDIUM)

- `line-height` — `leading-relaxed` (1.625) for body text; `leading-tight` for headings
- `font-scale` — `text-xs` · `text-sm` · `text-base` · `text-lg` · `text-xl` · `text-2xl` · `text-3xl` · `text-4xl`
- `weight-hierarchy` — `font-bold` headings, `font-medium` labels, `font-normal` body
- `color-semantic` — Use semantic pairs: `text-gray-900` on `bg-white`; `text-white` on `bg-indigo-600`
- `contrast-readability` — Always verify contrast with Tailwind color combinations; `text-gray-400` on white = ❌ (fails 4.5:1)
- `dark-mode-optional` — A2A-CRM is light mode only; do not add `dark:` variants without explicit request

---

### 7. Animation (MEDIUM)

**Alpine.js transition pattern:**
```blade
<div
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
>
```

- `duration-timing` — 150–200ms for micro-interactions; 200–300ms for panels/modals
- `transform-performance` — `transform` and `opacity` only; never animate `width`/`height`/`top`/`left`
- `reduced-motion` — Alpine transitions must degrade with `@media (prefers-reduced-motion: reduce)`
- `loading-skeleton` — Show skeleton shimmer for Livewire loading > 300ms; use `wire:loading` subtargets
- `easing` — `ease-out` for entering elements; `ease-in` for exiting

---

### 8. Forms & Feedback (MEDIUM)

- `input-labels` — Always visible `<label>` — never placeholder-only
- `error-placement` — Blade `@error` directive output below the related field
- `required-indicators` — `<span class="text-red-500">*</span>` on required fields
- `submit-feedback` — Livewire: disable + spinner on submit; then show success/error toast
- `toast-dismiss` — Auto-dismiss success toasts in 3–5s; error toasts persist until dismissed
- `confirmation-dialogs` — Alpine.js `confirm` modal before destructive Livewire actions
- `empty-states` — Meaningful empty state with icon + message + CTA when Livewire returns no results
- `inline-validation` — Validate on `blur` (Livewire `wire:model.blur`); not on every keystroke

**Blade form error pattern:**
```blade
<div>
    <label for="mobile" class="block text-sm font-medium text-gray-700">
        Mobile <span class="text-red-500">*</span>
    </label>
    <input
        id="mobile"
        wire:model.blur="mobile"
        type="tel"
        @class([
            'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
            'border-red-500 focus:border-red-500 focus:ring-red-500' => $errors->has('mobile'),
        ])
    />
    @error('mobile')
        <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
    @enderror
</div>
```

---

### 9. Navigation Patterns (HIGH)

- `sidebar-limit` — CRM sidebar: max 7 primary items with icons + labels; group related items
- `active-state` — Highlight current route with `bg-indigo-50 text-indigo-700 font-medium`
- `back-behavior` — Back link (`← Back to Leads`) on all detail/show views
- `breadcrumbs` — Use breadcrumbs for 3+ hierarchy levels (e.g., Leads > John Doe > Edit)
- `drawer-usage` — Secondary settings/filters in Alpine.js off-canvas drawer; not inline
- `modal-escape` — All modals: close on `Escape` key (`@keydown.escape.window="open = false"`) + backdrop click + × button
- `deep-linking` — All CRM screens reachable via UUID-based URLs; use `route()` helper

---

### 10. Charts & Data (LOW)

**A2A-CRM uses Chart.js via `<canvas>` in Blade + `@push('scripts')`:**

- `chart-type` — Line for trends (enrollment over time); Bar for comparisons (source); Donut for proportions (stage distribution)
- `accessible-colors` — Never red/green only; use Chart.js `borderDash` patterns for colorblind support
- `legend-visible` — Always render Chart.js `legend: { display: true }` above or beside chart
- `tooltip-on-hover` — Chart.js `tooltip` plugin always enabled; show exact values + labels
- `responsive-chart` — `responsive: true, maintainAspectRatio: false` in Chart.js options; wrap in fixed-height `div`
- `loading-chart` — Show `wire:loading` shimmer skeleton while Livewire fetches chart data
- `empty-data-state` — Display `<p class="text-gray-500 text-sm">No data yet</p>` when dataset is empty
- `data-table-alt` — Provide a toggle to show chart data as an accessible `<table>` for screen readers

```blade
{{-- Responsive Chart.js wrapper --}}
<div class="relative h-64 w-full">
    <canvas id="enrollmentChart" aria-label="Enrollment trend chart" role="img"></canvas>
</div>
```

---

## A2A-CRM Design System Reference

### Tailwind Component Classes (add to `resources/css/app.css`)

```css
@layer components {
    .btn-primary    { @apply inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed; }
    .btn-secondary  { @apply inline-flex items-center px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-md border border-gray-300 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-150 cursor-pointer; }
    .btn-danger     { @apply inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-150 cursor-pointer; }
    .btn-primary-sm { @apply inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500 transition-colors duration-150 cursor-pointer; }
    .btn-secondary-sm { @apply inline-flex items-center px-3 py-1.5 bg-white text-gray-700 text-xs font-medium rounded-md border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500 transition-colors duration-150 cursor-pointer; }
    .btn-ghost-sm   { @apply inline-flex items-center px-3 py-1.5 text-gray-500 text-xs font-medium rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-gray-400 transition-colors duration-150 cursor-pointer; }
    .input-field    { @apply block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm; }
    .label          { @apply block text-sm font-medium text-gray-700 mb-1; }
    .card           { @apply bg-white shadow-sm rounded-lg border border-gray-200 p-6; }
    .badge-hot      { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800; }
    .badge-warm     { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800; }
    .badge-cold     { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800; }
    .badge-converted { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800; }
    .badge-lost     { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500; }
}
```

### Spacing Scale
```
Section gap:     gap-6 (24px) or gap-8 (32px)
Card padding:    p-6 (24px)
Form field gap:  space-y-4 (16px)
Button gap:      gap-2 (8px)
Icon + text:     gap-1.5 (6px)
Page padding:    px-4 sm:px-6 lg:px-8
Page max-width:  max-w-7xl mx-auto
```

---

## A2A-CRM Page Patterns

### Lead List Pattern (BRD: CRM-LC-001)
```
Header: Page title + "Add Lead" primary CTA
Filters row: Search (Livewire live) + Temperature filter + Stage filter + Date range
Data table: Livewire paginated table with sortable columns
Row actions: View / Edit / Quick note (inline Alpine dropdown)
Empty state: "No leads found" + illustration + "Import" or "Add Lead" CTA
```

### Lead Detail Pattern (BRD: CRM-LC-018)
```
Breadcrumb: Leads > Lead Name
Two-column layout (lg):
  Left (2/3):  Activity timeline | Application details | Documents tab
  Right (1/3): Lead summary card | AI suggestion card | Quick actions | Tasks
```

### Analytics Dashboard Pattern (BRD: CRM-AR-001)
```
KPI tiles row (4 cols): Total leads | Conversion rate | Hot leads | This month
Funnel chart (full width): Livewire with date range Alpine picker
Side-by-side charts: Source breakdown (donut) | Counsellor performance (bar)
Data table below: Sortable, exportable lead source report
```

---

## Pre-Delivery Checklist

Before submitting any Blade/Livewire/Alpine UI:

### Visual Quality
- [ ] No emojis used as icons (Heroicons v2 only)
- [ ] All interactive elements have `cursor-pointer`
- [ ] Hover states present on all clickable elements (`hover:` Tailwind prefix)
- [ ] Transition duration 150–300ms (`transition-colors duration-150`)
- [ ] Consistent use of `btn-primary`, `btn-secondary`, `card` component classes

### Accessibility
- [ ] All form fields have visible `<label>` (not placeholder-only)
- [ ] Color contrast ≥ 4.5:1 for text (verify `text-gray-600` or darker on white)
- [ ] `aria-label` on all icon-only buttons
- [ ] Focus rings visible (`focus:ring-2 focus:ring-indigo-500`)
- [ ] Modals close on `Escape` key and backdrop click
- [ ] `@error` messages include `role="alert"` for screen readers

### Responsive
- [ ] Tested at 375px, 768px, 1024px, 1440px
- [ ] No horizontal scroll at any breakpoint
- [ ] Tables use `overflow-x-auto` wrapper on mobile
- [ ] Sidebar collapses to hamburger on mobile (`lg:block hidden`)

### Livewire / Alpine
- [ ] Search inputs use `wire:model.live.debounce.300ms`
- [ ] Submit buttons use `wire:loading.attr="disabled"` + spinner
- [ ] Loading states shown for operations > 300ms (`wire:loading`)
- [ ] Alpine modals use `x-trap` (or manual focus management)

### Security (DPDP + XSS)
- [ ] All user output uses `{{ }}` (never `{!! !!}` with unsanitised data)
- [ ] No PII displayed in logs or HTTP responses
- [ ] Consent checkbox present on all lead capture forms (BRD: CRM-CR-001)
- [ ] All forms include `@csrf`

---

## Prohibited Patterns

- ❌ Emojis as icons anywhere in the CRM UI
- ❌ `{!! $userInput !!}` without `Purify::clean()` — XSS risk
- ❌ Inline `style="..."` attributes — use Tailwind classes
- ❌ `wire:model.live` without `.debounce` on text inputs — causes N+1 requests
- ❌ Hardcoded route strings like `href="/crm/leads"` — always use `route('crm.leads.index')`
- ❌ `localStorage` for lead/student data — DPDP compliance risk
- ❌ Raw hex colors in Blade (`style="color: #6366f1"`) — use Tailwind semantic tokens
- ❌ `jQuery` — Alpine.js or vanilla JS only
- ❌ Exposing internal numeric IDs in URLs — always use UUID route model binding
