# Sprint Plan — Group B: Web Enquiry Forms & QR Capture
**BRD:** MEETCS-BRD-CRM-001 v1.0  
**Date:** 8 April 2026  
**Scope:** CRM-LC-001 · CRM-LC-002 · CRM-LC-009 · CRM-LC-015  
**Status:** 🔴 Not Started  
**Depends on:** Group A (LC-011, LC-014, LC-018) — ✅ Complete

---

## 1. BRD Requirements in Scope

| Req ID | Requirement | Priority | Current State |
|--------|-------------|----------|---------------|
| **CRM-LC-001** | System shall provide embeddable, customisable web enquiry forms (iFrame and native) for institution websites | Must Have | ❌ Zero implementation |
| **CRM-LC-002** | Forms shall support conditional field logic (show/hide fields based on prior answers) | Must Have | ❌ Zero implementation |
| **CRM-LC-009** | System shall support QR-code-based lead capture for walk-in enquiries and events | Must Have | ❌ Only `LeadSource::QR_CODE` enum exists |
| **CRM-LC-015** | UTM parameter tracking shall be supported for all web form and landing page submissions | Must Have | ⚠️ Partial — DB column + API validation done; public form auto-capture missing |

**DPDP compliance requirement active for all tasks:**  
- **CRM-CR-001** — Consent must be captured on the public form at the point of lead creation  
- **CRM-CR-002** — Consent record must store timestamp, IP address, and form version  

---

## 2. Architecture Design

### 2.1 New Entity: `WebForm`

A `WebForm` is an institution-owned, configurable lead capture form. Each form has a public URL slug, generates a QR code, and can be embedded as an iFrame. The form fields are stored as JSON allowing conditional logic rules.

```
web_forms (table)
├── id, uuid (public identifier)
├── institution_id, campus_id (multi-tenancy)
├── name (internal label e.g. "MBA 2026 Walk-in Form")
├── slug (URL-safe, unique per institution — e.g. "mba-2026")
├── fields (JSON — ordered array of field definitions, see §2.2)
├── is_active (bool)
├── embed_token (64-char random token for iFrame auth)
├── source (LeadSource enum — pre-sets lead source on submission)
├── redirect_url (nullable — where to send after submission)
├── consent_form_version (string — CRM-CR-002)
├── accent_color, logo_url, custom_css (branding)
├── timestamps, softDeletes
└── indexes: institution_id, slug, is_active
```

### 2.2 Field Definition Schema (JSON)

Each element in the `fields` JSON array is a field definition object:

```json
{
  "id": "programme_interest",
  "type": "select",
  "label": "Programme of Interest",
  "placeholder": "Select a programme",
  "required": true,
  "options": ["MBA", "MCA", "BBA", "BCA"],
  "show_if": null
}
```

`show_if` enables LC-002 conditional logic:

```json
{
  "id": "specialisation",
  "type": "select",
  "label": "Specialisation",
  "required": false,
  "options": ["Finance", "Marketing", "HR"],
  "show_if": {
    "field": "programme_interest",
    "operator": "equals",
    "value": "MBA"
  }
}
```

Supported field types: `text`, `tel`, `email`, `select`, `textarea`, `checkbox`, `hidden`  
Supported operators: `equals`, `not_equals`, `contains`

### 2.3 URL Structure

| URL | Auth | Purpose |
|-----|------|---------|
| `GET /f/{slug}` | Public | Render the web enquiry form |
| `POST /f/{slug}` | Public | Submit the form → create lead |
| `GET /f/{slug}/embed` | Public | Return minimal iFrame-safe HTML (no nav/header) |
| `GET /crm/forms` | Auth | Form management index |
| `GET /crm/forms/create` | Auth (can:crm.forms.create) | Form builder UI |
| `GET /crm/forms/{form:uuid}/edit` | Auth | Edit existing form |
| `GET /crm/forms/{form:uuid}/embed-code` | Auth | View embed code + QR |
| `GET /api/v1/crm/forms` | Auth Sanctum | API list |
| `POST /api/v1/crm/forms` | Auth Sanctum | API create |
| `PUT /api/v1/crm/forms/{form:uuid}` | Auth Sanctum | API update |
| `DELETE /api/v1/crm/forms/{form:uuid}` | Auth Sanctum | API delete (soft) |
| `GET /api/v1/crm/forms/{form:uuid}/qr` | Auth Sanctum | Download QR PNG (LC-009) |

### 2.4 QR Code Flow (LC-009)

```
Institution admin creates WebForm (slug: "open-day-2026")
        ↓
System generates QR code linking to:
  /f/open-day-2026?utm_source=qr&utm_medium=event&utm_campaign=open-day-2026
        ↓
Student scans QR on their phone
        ↓
Public form loads at /f/open-day-2026
Alpine.js reads UTM params from URL → injects into hidden fields (LC-015)
source field pre-set to "qr_code" 
        ↓
Student submits → Lead created with:
  source = qr_code
  source_utm_params = { utm_source: 'qr', utm_medium: 'event', utm_campaign: 'open-day-2026' }
  consent_given = true (from checkbox on public form)
```

### 2.5 UTM Auto-Capture (LC-015 completion)

The public form Alpine.js component reads URL query params on `x-init` and populates hidden `source_utm_params` fields automatically. This completes LC-015 — the API layer already stores UTM; now the capture end is wired.

---

## 3. Implementation Tasks

### Task B-01 — Install QR Code Package
**File:** `composer.json`  
**Command:** `composer require endroid/qr-code`  
**Notes:** `endroid/qr-code` v5 — generates PNG/SVG, no GD extension required (uses Imagick or GD auto-detected).

---

### Task B-02 — Migration: `web_forms` table
**File:** `database/migrations/2026_04_12_000001_create_web_forms_table.php`

Columns:
- `id`, `uuid` (unique)
- `institution_id` (indexed), `campus_id` (nullable, indexed)
- `name` string(120)
- `slug` string(80) — unique per institution via composite unique index `[institution_id, slug]`
- `fields` json — field definition array
- `is_active` boolean default true
- `embed_token` string(64) unique — for iFrame auth header validation
- `source` string — default `website_organic` (LeadSource enum value)
- `redirect_url` string nullable
- `consent_form_version` string(30) — CRM-CR-002
- `accent_color` string(7) nullable — hex e.g. `#6366F1`
- `logo_url` string nullable
- `custom_css` text nullable
- `timestamps()`, `softDeletes()`
- Indexes: `institution_id`, `is_active`, `[institution_id, slug]` (unique)

---

### Task B-03 — Model: `WebForm`
**File:** `app/Models/CRM/WebForm.php`

- `declare(strict_types=1)`
- `use HasUuids, SoftDeletes`
- `InstitutionScope` global scope
- `$fillable` — all columns above
- Casts: `fields` → `array`, `is_active` → `boolean`, `source` → `LeadSource::class`
- Relationship: `belongsTo(Institution::class)`, `belongsTo(Campus::class)`
- Helper: `publicUrl(): string` → `url('/f/' . $this->slug)`
- Helper: `embedUrl(): string` → `url('/f/' . $this->slug . '/embed')`

---

### Task B-04 — DTO: `CreateWebFormDTO`
**File:** `app/DTOs/CRM/CreateWebFormDTO.php`

```
readonly: name, slug, fields[], source, isActive, redirectUrl,
          consentFormVersion, accentColor, logoUrl, campusId
```

Static factory `fromRequest(array $validated): self`

---

### Task B-05 — Repository Interface + Implementation
**Files:**
- `app/Repositories/CRM/WebForm/WebFormRepositoryInterface.php`
- `app/Repositories/CRM/WebForm/EloquentWebFormRepository.php`

Methods:
- `create(CreateWebFormDTO, int $institutionId): WebForm`
- `findBySlug(string $slug, int $institutionId): ?WebForm`
- `findByUuidOrFail(string $uuid): WebForm`
- `update(WebForm, array $data): WebForm`
- `softDelete(WebForm): void`
- `paginate(array $filters, int $perPage): LengthAwarePaginator`
- `generateUniqueSlug(string $name, int $institutionId): string`

---

### Task B-06 — Events
**Files:**
- `app/Events/CRM/WebFormCreatedEvent.php`
- `app/Events/CRM/WebFormSubmittedEvent.php` — fired after lead created from public form submission

---

### Task B-07 — Service: `WebFormService`
**File:** `app/Services/CRM/WebForm/WebFormService.php`

Methods:
```php
// BRD: CRM-LC-001 — Create and persist a new web form configuration
create(CreateWebFormDTO $dto, int $institutionId): WebForm

// BRD: CRM-LC-001 — Update form configuration
update(WebForm $form, array $data): WebForm

// BRD: CRM-LC-001 — Deactivate form
delete(WebForm $form): void

// BRD: CRM-LC-009 — Generate QR code PNG for a form's public URL
generateQrCode(WebForm $form): string  // returns raw PNG binary

// BRD: CRM-LC-001 — Generate iFrame embed snippet
generateEmbedSnippet(WebForm $form): string

// BRD: CRM-LC-001 + LC-002 — Process public form submission → create Lead
// Reuses LeadService::create() internally
handlePublicSubmission(WebForm $form, array $data, string $ip): Lead
```

`generateQrCode()` uses `endroid/qr-code`:
```php
$qr = new QrCode(url: $form->publicUrl() . '?utm_source=qr&utm_medium=event');
$writer = new PngWriter();
return $writer->write($qr)->getString();
```

`handlePublicSubmission()`:
1. Validates required fields from `$form->fields`
2. Merges `source` from `$form->source`
3. Merges UTM params from submitted data
4. Calls `LeadService::create()` with `CreateLeadDTO`
5. Dispatches `WebFormSubmittedEvent`

---

### Task B-08 — FormRequests
**Files:**
- `app/Http/Requests/Api/CRM/StoreWebFormRequest.php` — auth, validates form config
- `app/Http/Requests/Api/CRM/UpdateWebFormRequest.php` — auth, sometimes rules
- `app/Http/Requests/Public/PublicFormSubmissionRequest.php` — **no auth**, validates dynamic fields + consent

`PublicFormSubmissionRequest` validation rules:
```php
'first_name'           => ['required', 'string', 'max:80'],
'last_name'            => ['required', 'string', 'max:80'],
'mobile'               => ['required', 'regex:/^[6-9]\d{9}$/'],
'email'                => ['nullable', 'email:rfc', 'max:160'],
'source'               => ['sometimes', Rule::enum(LeadSource::class)],
'source_utm_params'    => ['nullable', 'array'],
'source_utm_params.*'  => ['nullable', 'string', 'max:200'],
'consent_given'        => ['required', 'accepted'],   // CRM-CR-001
'consent_form_version' => ['required', 'string'],
// Dynamic programme field
'programme_interest'   => ['nullable', 'string', 'max:200'],
```

---

### Task B-09 — API Resource: `WebFormResource`
**File:** `app/Http/Resources/CRM/WebFormResource.php`

Exposes: `uuid`, `name`, `slug`, `fields`, `is_active`, `source`, `public_url`, `embed_url`, `redirect_url`, `consent_form_version`, `accent_color`  
Never exposes: `id`, `institution_id`, `embed_token`

---

### Task B-10 — Controllers

**File:** `app/Http/Controllers/Api/CRM/WebFormController.php`  
Thin API controller: FormRequest → Gate → Service → Resource  
Implement: `index`, `store`, `show`, `update`, `destroy`, `qr` (download PNG)

**File:** `app/Http/Controllers/Web/CRM/WebFormWebController.php`  
Auth web controller: `index`, `create`, `store`, `edit`, `update`, `embedCode`

**File:** `app/Http/Controllers/Public/PublicFormController.php`  
Public controller — **no auth middleware**:
- `show(string $slug): View` — render public form
- `embed(string $slug): View` — render iFrame-only version (no nav/header)
- `submit(PublicFormSubmissionRequest $request, string $slug): RedirectResponse|JsonResponse`
  - Resolves `WebForm` by slug only (no auth scope — uses `withoutGlobalScopes()` + `where('slug', $slug)` + `where('is_active', true)`)
  - Calls `WebFormService::handlePublicSubmission()`
  - Returns JSON for XHR or redirect for plain HTML submission

---

### Task B-11 — Routes

**`routes/web.php` — Public (outside auth middleware):**
```php
// BRD: CRM-LC-001 — Public web enquiry form
Route::get('/f/{slug}', [PublicFormController::class, 'show'])->name('public.form.show');
Route::get('/f/{slug}/embed', [PublicFormController::class, 'embed'])->name('public.form.embed');
Route::post('/f/{slug}', [PublicFormController::class, 'submit'])->name('public.form.submit');
```

**`routes/web.php` — Inside auth middleware:**
```php
Route::prefix('crm')->name('crm.')->group(function () {
    Route::resource('forms', WebFormWebController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])
        ->middleware(['can:crm.forms.view']);
    Route::get('forms/{form:uuid}/embed-code', [WebFormWebController::class, 'embedCode'])
        ->name('forms.embed-code');
});
```

**`routes/api.php`:**
```php
Route::apiResource('forms', WebFormController::class);
Route::get('forms/{form:uuid}/qr', [WebFormController::class, 'qr'])->name('crm.forms.qr');
```

---

### Task B-12 — Permissions (Seeder update)
**File:** `database/seeders/PermissionSeeder.php`

Add permissions:
- `crm.forms.view`
- `crm.forms.create`
- `crm.forms.edit`
- `crm.forms.delete`

Role assignments:
- `super-admin`, `institution-admin`, `admissions-manager` → all 4
- `counsellor` → `crm.forms.view` only

---

### Task B-13 — Views: Auth (Form Management)

**`resources/views/crm/forms/index.blade.php`**  
Form management table. Columns: Name, Slug, Source, Status (active/inactive), Submissions (count), Actions (Edit, Embed Code, QR, Delete).  
Filter: active/inactive toggle.

**`resources/views/crm/forms/create.blade.php` / `edit.blade.php`**  
Form builder UI with:
- Basic settings: Name, Slug (auto-generated from name), Source, Redirect URL, Consent Version
- Branding: Accent Colour picker, Logo URL
- Field builder (Alpine.js drag-and-drop concept):
  - Add field button → field type selector → label/placeholder/required/options
  - Conditional logic toggle per field → `show_if` rule builder (select field → operator → value)
  - Reorder fields (up/down arrows)
- Live preview panel (right side) — iFrame pointing to `/f/{slug}/embed`

**`resources/views/crm/forms/embed-code.blade.php`**  
Embed code card with:
- Generated `<iframe>` snippet with copy button
- QR code PNG inline (base64) with download button
- Public URL with copy button

---

### Task B-14 — Views: Public Form (LC-001 + LC-002 + LC-015)

**`resources/views/public/form/show.blade.php`**  
Minimal layout (no sidebar/nav) — just the enquiry form card. Branding from `$form->accent_color` and `$form->logo_url`.

Alpine.js component `publicForm()`:
```javascript
// LC-015 — Auto-capture UTM from URL on init
x-init: function() {
    const params = new URLSearchParams(window.location.search);
    ['utm_source','utm_medium','utm_campaign','utm_term','utm_content']
        .forEach(k => { if (params.get(k)) this.form.source_utm_params[k] = params.get(k); });
    // Pre-set source if ?source= present
    if (params.get('source')) this.form.source = params.get('source');
}
```

Dynamic field rendering (LC-002 conditional logic):
```html
<template x-for="field in visibleFields" :key="field.id">
    <!-- renders input/select/textarea based on field.type -->
</template>
```

`visibleFields` computed property evaluates `show_if` rules against current form values.

Consent checkbox (CRM-CR-001) — always shown, required.  
Success state: inline thank-you message or redirect to `$form->redirect_url`.  
Error state: inline field-level validation errors from API JSON response.

**`resources/views/public/form/embed.blade.php`**  
Same as `show.blade.php` but uses `x-layouts.embed` (bare HTML, no CRM chrome, iFrame-friendly).

---

### Task B-15 — Layout: `x-layouts.embed`
**File:** `resources/views/components/layouts/embed.blade.php`  
Bare HTML shell: `<html><head>Vite assets</head><body>{{ $slot }}</body></html>`  
No sidebar, no navbar, responsive within iFrame.

---

### Task B-16 — Service Provider Binding
**File:** `app/Providers/CRM/CrmModuleServiceProvider.php`

Add binding:
```php
WebFormRepositoryInterface::class => EloquentWebFormRepository::class,
```

---

### Task B-17 — Tests

**File:** `tests/Feature/CRM/Api/WebFormApiTest.php`
- Can create a web form (LC-001)
- Slug is auto-namespaced per institution
- Cannot create form in another institution's scope
- Form with `is_active=false` returns 404 on public URL

**File:** `tests/Feature/CRM/Public/PublicFormSubmissionTest.php`
- Public form renders without auth (LC-001)
- Submitting valid data creates a Lead with correct source (LC-001)
- Consent not given → 422 (CRM-CR-001)
- UTM params from URL are stored on the lead (LC-015)
- source=qr_code is stored correctly (LC-009)
- Conditional field validation — hidden/required field not triggered when condition false (LC-002)
- Cross-institution slug isolation — slug from institution A not accessible on institution B's public URL

**File:** `tests/Feature/CRM/Api/WebFormQrTest.php`
- QR endpoint returns `image/png` content-type (LC-009)
- QR URL encodes correct UTM params

---

## 4. File Manifest

```
New Files (18):
database/migrations/2026_04_12_000001_create_web_forms_table.php
app/Models/CRM/WebForm.php
app/DTOs/CRM/CreateWebFormDTO.php
app/Repositories/CRM/WebForm/WebFormRepositoryInterface.php
app/Repositories/CRM/WebForm/EloquentWebFormRepository.php
app/Events/CRM/WebFormCreatedEvent.php
app/Events/CRM/WebFormSubmittedEvent.php
app/Services/CRM/WebForm/WebFormService.php
app/Http/Requests/Api/CRM/StoreWebFormRequest.php
app/Http/Requests/Api/CRM/UpdateWebFormRequest.php
app/Http/Requests/Public/PublicFormSubmissionRequest.php
app/Http/Resources/CRM/WebFormResource.php
app/Http/Controllers/Api/CRM/WebFormController.php
app/Http/Controllers/Web/CRM/WebFormWebController.php
app/Http/Controllers/Public/PublicFormController.php
resources/views/crm/forms/index.blade.php
resources/views/crm/forms/create.blade.php
resources/views/crm/forms/edit.blade.php
resources/views/crm/forms/embed-code.blade.php
resources/views/public/form/show.blade.php
resources/views/public/form/embed.blade.php
resources/views/components/layouts/embed.blade.php
tests/Feature/CRM/Api/WebFormApiTest.php
tests/Feature/CRM/Public/PublicFormSubmissionTest.php
tests/Feature/CRM/Api/WebFormQrTest.php

Modified Files (5):
routes/web.php                                     (public + auth form routes)
routes/api.php                                     (form API resource + QR endpoint)
app/Providers/CRM/CrmModuleServiceProvider.php     (repository binding)
database/seeders/PermissionSeeder.php              (4 new form permissions)
database/seeders/RoleSeeder.php                    (assign form permissions to roles)
composer.json                                      (endroid/qr-code)
```

---

## 5. BRD Traceability

| Req ID | Satisfied By |
|--------|-------------|
| CRM-LC-001 | `WebForm` model + migration + `WebFormService::create()` + `PublicFormController::show/submit()` + `show.blade.php` + iFrame embed |
| CRM-LC-002 | `fields[].show_if` JSON schema + Alpine.js `visibleFields` computed + `PublicFormSubmissionRequest` dynamic validation |
| CRM-LC-009 | `WebFormService::generateQrCode()` using `endroid/qr-code` + `WebFormController::qr()` + QR URL with `LeadSource::QR_CODE` + UTM presets |
| CRM-LC-015 | Alpine.js `x-init` URL param reader on public form populates `source_utm_params` (DB + API layer was already done in Group A) |
| CRM-CR-001 | Consent checkbox on `show.blade.php` + `required accepted` rule in `PublicFormSubmissionRequest` |
| CRM-CR-002 | `consent_form_version` from `$form->consent_form_version` + `consent_ip` from `$request->ip()` passed to `CreateLeadDTO` |

---

## 6. Security Checklist

- [ ] Public routes (`/f/*`) use **rate limiting** — `throttle:60,1` per IP to prevent spam
- [ ] Public form submission uses `PublicFormSubmissionRequest` — all inputs validated before reaching service
- [ ] `PublicFormController` resolves `WebForm` by slug **with** `where('is_active', true)` — inactive forms return 404
- [ ] `embed_token` is never exposed in API responses or public HTML — internal auth only
- [ ] `WebFormController` (API) and `WebFormWebController` (web) enforce `crm.forms.*` gate checks — institution-scoped via `InstitutionScope`
- [ ] No PII logged from public form submissions (CRM-CR-002)
- [ ] `custom_css` stored as text but sanitised before rendering (strip `<script>`, `javascript:` — use `strip_tags()` + allowlist in `WebFormService`)
- [ ] QR code endpoint requires auth — QR PNG is generated server-side, not stored on disk

---

## 7. Definition of Done

- [ ] All 25 new files created
- [ ] All 6 modified files updated
- [ ] `composer require endroid/qr-code` installed
- [ ] `php artisan migrate` runs cleanly
- [ ] Public form renders at `/f/{slug}` without authentication
- [ ] Submitting public form creates a `Lead` with correct `source`, `source_utm_params`, `consent_given`, `consent_ip`, `consent_form_version`
- [ ] QR code downloads as valid PNG from auth API endpoint
- [ ] Conditional field shows/hides correctly based on prior answer (LC-002)
- [ ] UTM params in URL are auto-captured into lead record (LC-015)
- [ ] All 3 test files pass — minimum 15 test cases
- [ ] No regressions in Group A tests (`LeadApiTest`, `DuplicateDetectionTest`)
- [ ] Larastan level 5 passes on all new PHP files

---

## 8. Out of Scope (Group B)

The following are **NOT** in Group B — deferred to later groups:

| Req ID | Reason for Deferral |
|--------|---------------------|
| CRM-LC-003 | Google Ads webhook — requires external Google Lead Form setup (Group C) |
| CRM-LC-004 | Meta Lead Ads API — requires Meta app review (Group C) |
| CRM-LC-005 | Landing page drag-and-drop builder — Should Have, Phase 2 |
| CRM-LC-006 | Live chat widget — Should Have, Phase 2 |
| CRM-LC-007 | WhatsApp auto-lead — requires BSP integration (Group D) |
| CRM-LC-008 | Education portal CSV import — Group C |
| CRM-LC-016 | Multi-touch attribution — Should Have, Phase 2 |
| CRM-LC-017 | Cost-per-lead — Should Have, Phase 2 |
