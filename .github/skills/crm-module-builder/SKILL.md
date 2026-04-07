---
name: crm-module-builder
description: "Scaffold a complete A2A-CRM feature module end-to-end. Use when asked to build a new CRM feature from BRD requirements — generates the full Laravel + Blade stack: migration, model, repository, service, events, jobs, FormRequest, JsonResource, controller, routes, Blade views, and Alpine.js/Livewire components. Use for: new CRM features, scaffolding BRD requirements, module generation."
argument-hint: "BRD Req ID(s) and feature description"
---

# CRM Module Builder

Generates a complete, production-ready A2A-CRM feature module following all Laravel + Blade standards in the workspace.

## When to Use

- Building a new CRM feature from a BRD requirement
- Scaffolding a module skeleton before implementation
- Generating consistent boilerplate for all 19 BRD modules

## Procedure

### Step 1 — Understand the Requirement

Read the BRD section in [BRD_A2A_Educational_CRM_v1.0_1.md](../../../BRD_A2A_Educational_CRM_v1.0_1.md) for the Req IDs provided.

Identify:
- Entity name(s) and relationships
- Which data is PII (needs DPDP treatment)
- Which operations are async (need jobs + queues)
- Which state changes fire events
- BRD MoSCoW priority (Must Have = Phase 1)

### Step 2 — Generate Database Layer

Create migration following [crm-data-model instructions](../../instructions/crm-data-model.instructions.md):
- Standard columns: `id`, `uuid`, `institution_id`, `campus_id`, `timestamps()`, `softDeletes()`
- Encrypted columns for any PII fields
- DPDP consent columns if entity captures personal data
- All required indexes
- Reversible `down()`

Create the Eloquent model:
- `declare(strict_types=1)`
- `use HasUuids, SoftDeletes`
- `InstitutionScope` in `booted()`
- Explicit `$fillable`
- Enum casts + encrypted casts

### Step 3 — Generate Business Logic Layer

Create Repository interface + implementation:
```
app/Repositories/CRM/{Module}/{Entity}Repository.php
app/Repositories/CRM/{Module}/{Entity}RepositoryInterface.php
```

Create Service:
```
app/Services/CRM/{Module}/{Feature}Service.php
```
- Annotate each method with BRD Req ID
- Inject repository via constructor
- Dispatch Events on state changes
- Dispatch Jobs for async work (AI, ERP, comms)

### Step 4 — Generate HTTP Layer

Create FormRequest (validate all inputs, consent if PII):
```
app/Http/Requests/CRM/Store{Entity}Request.php
```

Create JsonResource (expose uuid, never id/institution_id):
```
app/Http/Resources/CRM/{Entity}Resource.php
```

Create Controller (thin: FormRequest → Gate → Service → Resource):
```
app/Http/Controllers/CRM/{Module}/{Entity}Controller.php
```

Add routes under `/api/v1/crm/`.

### Step 5 — Generate Events + Jobs

Event for each state transition:
```
app/Events/CRM/{Entity}{State}Event.php
```

Job for each async operation:
```
app/Jobs/CRM/{Operation}Job.php   # implements ShouldQueue, $tries=3, $backoff=60
```

### Step 6 — Generate Frontend

Blade view directory:
```
resources/views/crm/{module}/
├── index.blade.php    # list / pipeline view
├── show.blade.php     # detail view
└── create.blade.php   # create / edit form
```

Livewire component (if reactive UI is needed):
```
app/Livewire/CRM/{Module}/{Entity}Table.php
resources/views/livewire/crm/{module}/{entity}-table.blade.php
```
- `wire:model.live` for live search/filter bindings
- Alpine.js `x-data` for dropdowns, modals, and toggles
- Tailwind CSS layout with `@class` for conditional styling
- AI suggestion cards (`<x-crm.ai-suggestion>` component) with Accept/Edit/Dismiss (BRD: CRM-AI-011)

### Step 7 — BRD Coverage Validation

After scaffolding, output:

```markdown
## Module Scaffold Complete

**Feature:** {name}
**BRD Requirements Covered:**
- CRM-XX-001 ✅
- CRM-XX-002 ✅

**Files Generated:**
- database/migrations/...
- app/Models/CRM/...
- app/Services/CRM/...
[full list]

**DPDP Fields:** [consent_given, consent_timestamp, opt_out, ...]
**Async Jobs:** [list]
**Events Fired:** [list]
**Phase:** Phase 1 (Must Have) / Phase 2 (Should Have)

**Next Steps:**
1. Run `php artisan migrate`
2. Register events in `EventServiceProvider`
3. Bind repository interface in `CrmServiceProvider`
4. Run `/generate-test-suite` for this module
```

## Key Reference Files

- [Laravel Standards](../../instructions/laravel-backend-standards.instructions.md)
- [Blade Frontend Standards](../../instructions/blade-frontend-standards.instructions.md)
- [DPDP Compliance](../../instructions/dpdp-compliance.instructions.md)
- [Data Model](../../instructions/crm-data-model.instructions.md)
- [API Design](../../instructions/api-design-standards.instructions.md)
