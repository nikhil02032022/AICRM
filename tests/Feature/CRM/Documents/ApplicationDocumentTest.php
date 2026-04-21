<?php

declare(strict_types=1);

// BRD: CRM-DM-002, DM-003, DM-004, DM-008 — Upload + review lifecycle with encryption.

use App\Enums\CRM\Documents\DocumentStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Documents\DocumentChecklist;
use App\Models\CRM\Documents\DocumentChecklistItem;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use App\Services\CRM\Documents\ApplicationDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('encrypted_documents');
});

function docFixtures(): array
{
    $institution = Institution::factory()->create();
    $programme = CrmProgramme::factory()->for($institution)->create();
    $lead = Lead::factory()->for($institution)->create();
    $application = Application::factory()
        ->for($lead, 'lead')->for($institution)
        ->create(['programme_id' => $programme->id]);

    $checklist = DocumentChecklist::factory()->create([
        'institution_id' => $institution->id,
        'programme_id'   => $programme->id,
    ]);
    $item = DocumentChecklistItem::factory()->create([
        'institution_id' => $institution->id,
        'document_checklist_id' => $checklist->id,
        'allowed_mime' => ['application/pdf'],
        'max_size_kb'  => 1024,
    ]);

    return compact('application', 'item');
}

it('uploads, encrypts, approves, and rejects documents', function () {
    ['application' => $app, 'item' => $item] = docFixtures();
    $service = app(ApplicationDocumentService::class);
    $file = UploadedFile::fake()->createWithContent('x.pdf', 'binary-contents');
    // fake MIME — UploadedFile::fake returns text/plain by default; stub via custom.
    $file = UploadedFile::fake()->create('x.pdf', 1, 'application/pdf');

    $doc = $service->upload($app, $item, $file);

    expect($doc->status)->toBe(DocumentStatus::SUBMITTED);
    expect($doc->storage_path)->not->toBeNull();
    expect(Storage::disk('encrypted_documents')->exists($doc->storage_path))->toBeTrue();

    $service->approve($doc, 'ok');
    expect($doc->refresh()->status)->toBe(DocumentStatus::VERIFIED);

    $service->reject($doc, 'bad scan');
    $doc->refresh();
    expect($doc->status)->toBe(DocumentStatus::REJECTED);
    expect($doc->rejection_reason)->toBe('bad scan');
});

it('rejects over-size uploads', function () {
    ['application' => $app, 'item' => $item] = docFixtures();
    $service = app(ApplicationDocumentService::class);
    // 2048 KB > item limit of 1024 KB.
    $file = UploadedFile::fake()->create('big.pdf', 2048, 'application/pdf');

    $this->expectException(DomainException::class);
    $service->upload($app, $item, $file);
});

it('rejects disallowed mime types', function () {
    ['application' => $app, 'item' => $item] = docFixtures();
    $service = app(ApplicationDocumentService::class);
    $file = UploadedFile::fake()->create('x.exe', 10, 'application/octet-stream');

    $this->expectException(DomainException::class);
    $service->upload($app, $item, $file);
});
