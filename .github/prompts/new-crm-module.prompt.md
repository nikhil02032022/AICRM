---
description: "Scaffold a complete A2A-CRM feature module end-to-end from a BRD requirement. Generates Service, Repository, Model, Migration, Events, Jobs, FormRequest, JsonResource, Controller, Routes, and Blade view structure."
agent: "agent"
tools: [read, edit, search, todo]
argument-hint: "BRD Req ID(s) and feature name, e.g. 'CRM-LC-018 duplicate detection service'"
---

You are scaffolding a complete A2A-CRM feature module. Follow the project conventions exactly.

## Instructions

Given the BRD requirement(s) and feature name provided, generate the following file set:

### 1. Migration
- Full `up()` with all columns, indexes
- Full working `down()`
- `institution_id` + `campus_id` scoped
- `uuid` unique column
- `softDeletes()`
- All required indexes

### 2. Model (`app/Models/CRM/{ModelName}.php`)
- `declare(strict_types=1)`
- `use HasUuids, SoftDeletes`
- `static::addGlobalScope(new InstitutionScope())`
- Explicit `$fillable` (no `['*']`)
- `$casts` for encrypted PII fields and enums
- Relationships (as needed)

### 3. Repository (`app/Repositories/CRM/{Module}/{ModelName}Repository.php`)
- Interface + implementation
- `findByUuid()`, `paginateForUser()`, `create()`, `update()`
- All queries include institution scope

### 4. Service (`app/Services/CRM/{Module}/{FeatureName}Service.php`)
- Business logic only
- Constructor injection of repository
- BRD Req ID comment on each method
- Dispatches Events on state changes
- Dispatches Jobs for async operations

### 5. Events + Listeners
- Event class for the primary state change
- Listener stubs registered in `EventServiceProvider`

### 6. Job (if async operations needed)
- `implements ShouldQueue`
- `$tries = 3`, `$backoff = 60`
- Idempotent `handle()` method

### 7. FormRequest (`app/Http/Requests/CRM/{Name}Request.php`)
- All validation rules
- DPDP fields if PII is captured (`consent_given` = `required|accepted`)

### 8. JsonResource (`app/Http/Resources/CRM/{Name}Resource.php`)
- Returns `uuid`, never `id`
- No `institution_id` exposed
- Conditional loads with `whenLoaded()`

### 9. Controller (`app/Http/Controllers/CRM/{Name}Controller.php`)
- Thin: FormRequest → Gate::authorize() → Service → Resource
- RESTful methods only

### 10. Route
- Under `/api/v1/crm/`
- UUID-based route model binding

### 11. Blade View Structure
- `resources/views/crm/{module}/index.blade.php` — list view
- `resources/views/crm/{module}/show.blade.php` — detail view
- `resources/views/crm/{module}/create.blade.php` — create/edit form
- Livewire component (`app/Livewire/CRM/`) for any reactive UI
- Alpine.js `x-data` for client-only state (dropdowns, modals)
- Tailwind CSS layout skeleton

## Output Format

Generate each file with its full path, complete code. Include BRD Req ID annotations. After all files, show:

```
## BRD Coverage
Requirements covered: [list]
DPDP fields included: [yes/no, which ones]
Queue jobs dispatched: [list]
Events fired: [list]
Tests to write: [key test cases]
```
