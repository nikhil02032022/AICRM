<?php

declare(strict_types=1);

use App\Jobs\CRM\GenerateOfferLetterJob;
use App\Jobs\CRM\SendOfferLetterJob;
use App\Models\CRM\Application;
use App\Models\CRM\Lead;
use App\Models\CRM\OfferLetter;
use App\Models\CRM\OfferLetterTemplate;
use App\Models\CRM\CrmProgramme;
use App\Models\User;
use App\Services\CRM\Application\OfferLetterRenderService;
use App\Services\CRM\Communication\CommunicationEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('s3');
    Queue::fake();
    $this->user = User::factory()->create(['institution_id' => 1]);
    $this->lead = Lead::factory()->create(['institution_id' => 1]);
    $this->programme = CrmProgramme::factory()->create(['institution_id' => 1]);
    $this->application = Application::factory()
        ->for($this->lead, 'lead')
        ->for($this->programme, 'programme')
        ->create(['institution_id' => 1]);
});

it('can generate offer letter for application', function () {
    $this->actingAs($this->user);
    $response = $this->post(route('crm.applications.offers.store', $this->application->uuid), [
        'expires_in_days' => 30,
        'reason' => 'Merit-based selection',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('offer_letters', [
        'application_uuid' => $this->application->uuid,
        'lead_uuid' => $this->lead->uuid,
        'status' => 'pending',
    ]);
});

it('offer letter generation dispatches pdf job', function () {
    $this->actingAs($this->user);
    $this->post(route('crm.applications.offers.store', $this->application->uuid), [
        'expires_in_days' => 30,
    ]);
    Queue::assertPushed(GenerateOfferLetterJob::class);
});

it('can render offer letter template to pdf', function () {
    $template = OfferLetterTemplate::factory()->create(['institution_id' => 1]);
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create(['institution_id' => 1]);
    $renderService = app(OfferLetterRenderService::class);
    $html = $renderService->renderTemplate(
        template: $template,
        lead: $this->lead,
        application: $this->application,
        offerLetter: $offerLetter,
    );
    expect($html)->toContain($this->lead->full_name)
        ->toContain($this->programme->name);
});

it('can record offer acceptance', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'expires_at' => now()->addDays(30),
        ]);
    $this->actingAs($this->user);
    $response = $this->post(route('crm.offer_letters.accept', $offerLetter->uuid), [
        'notes' => 'Accepted by applicant',
    ]);
    $response->assertRedirect();
    $offerLetter->refresh();
    expect($offerLetter->isAccepted())->toBeTrue();
    expect($offerLetter->acceptance_recorded_at)->not->toBeNull();
    expect($offerLetter->acceptance_ip)->not->toBeNull();
});

it('can record offer decline', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
        ]);
    $this->actingAs($this->user);
    $response = $this->post(route('crm.offer_letters.decline', $offerLetter->uuid), [
        'reason' => 'Better offer received elsewhere',
    ]);
    $response->assertRedirect();
    $offerLetter->refresh();
    expect($offerLetter->isDeclined())->toBeTrue();
    expect($offerLetter->declined_at)->not->toBeNull();
    expect($offerLetter->decline_reason)->toBe('Better offer received elsewhere');
});

it('can send offer letter via email', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'pdf_path' => 'crm/offer-letters/1/test.pdf',
        ]);
    Storage::disk('s3')->put($offerLetter->pdf_path, 'mock pdf content');
    $this->actingAs($this->user);
    $response = $this->post(route('crm.offer_letters.send', $offerLetter->uuid), [
        'channel' => 'email',
    ]);
    $response->assertRedirect();
    $offerLetter->refresh();
    expect($offerLetter->sent_via)->toBe('email');
    expect($offerLetter->sent_at)->not->toBeNull();
});

it('send offer letter job updates delivery status and logs activity', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'pdf_path' => 'crm/offer-letters/1/test.pdf',
        ]);
    $job = new SendOfferLetterJob($offerLetter, 'email');
    $job->handle(app(CommunicationEngineService::class));
    $offerLetter->refresh();
    expect($offerLetter->status)->toBe('sent');
    expect($offerLetter->sent_via)->toBe('email');
    expect($offerLetter->delivery_status)->toBe('sent');
    expect($offerLetter->delivery_message_id)->not->toBeNull();
});

it('send offer letter job handles delivery failure', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'pdf_path' => 'crm/offer-letters/1/test.pdf',
        ]);
    $mockComms = $this->getMockBuilder(CommunicationEngineService::class)
        ->onlyMethods(['sendOfferLetter'])
        ->getMock();
    $mockComms->method('sendOfferLetter')->willThrowException(new Exception('Simulated failure'));
    $job = new SendOfferLetterJob($offerLetter, 'email');
    $job->handle($mockComms);
    $offerLetter->refresh();
    expect($offerLetter->delivery_status)->toBe('failed');
});

it('cannot accept expired offer', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'expires_at' => now()->subDays(1),
        ]);
    $this->actingAs($this->user);
    $response = $this->get(route('crm.offer_letters.accept.form', $offerLetter->uuid));
    $response->assertStatus(422);
});

it('api can list offers for application', function () {
    $offer1 = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create(['institution_id' => 1]);
    $offer2 = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create(['institution_id' => 1]);
    $response = $this->actingAs($this->user)
        ->getJson(route('api.v1.crm.applications.offers.index', $this->application->uuid));
    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});

it('api can generate offer letter', function () {
    $response = $this->actingAs($this->user)
        ->postJson(route('api.v1.crm.applications.offers.store', $this->application->uuid), [
            'expires_in_days' => 30,
        ]);
    $response->assertStatus(201);
    $response->assertJsonStructure(['success', 'data' => ['uuid', 'status']]);
});

it('offer letter provides download signed url', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'pdf_path' => 'crm/offer-letters/1/test.pdf',
        ]);
    Storage::disk('s3')->put($offerLetter->pdf_path, 'mock pdf');
    $this->actingAs($this->user);
    $response = $this->getJson(route('api.v1.crm.offers.download', $offerLetter->uuid));
    $response->assertOk();
    $response->assertJsonStructure(['success', 'data' => ['download_url', 'expires_in_minutes']]);
});

it('offer letter cannot be accepted twice', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'accepted',
            'acceptance_recorded_at' => now(),
        ]);
    $this->actingAs($this->user);
    $response = $this->post(route('crm.offer_letters.accept', $offerLetter->uuid));
    $response->assertStatus(422);
});

// AP-014: Conditional offer management tests

it('can create a conditional offer with required documents', function () {
    $this->actingAs($this->user);
    $response = $this->post(route('crm.applications.offers.store', $this->application->uuid), [
        'expires_in_days' => 30,
        'conditional' => true,
        'required_documents' => ['marksheet', 'id_proof'],
    ]);
    $response->assertRedirect();
    $offer = \App\Models\CRM\OfferLetter::where('application_uuid', $this->application->uuid)->first();
    expect($offer->conditional)->toBeTrue()
        ->and($offer->required_documents)->toContain('marksheet')
        ->and($offer->required_documents)->toContain('id_proof')
        ->and($offer->document_verification_status)->toBe(['marksheet' => false, 'id_proof' => false]);
});

it('blocks acceptance of conditional offer when documents not verified', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'expires_at' => now()->addDays(30),
            'conditional' => true,
            'required_documents' => ['marksheet'],
            'document_verification_status' => ['marksheet' => false],
        ]);
    $this->actingAs($this->user);
    $response = $this->post(route('crm.offer_letters.accept', $offerLetter->uuid));
    $response->assertStatus(422);
    expect($offerLetter->refresh()->status)->toBe('generated');
});

it('allows acceptance of conditional offer once all documents verified', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'expires_at' => now()->addDays(30),
            'conditional' => true,
            'required_documents' => ['marksheet'],
            'document_verification_status' => ['marksheet' => true],
        ]);
    $this->actingAs($this->user);
    $response = $this->post(route('crm.offer_letters.accept', $offerLetter->uuid));
    $response->assertRedirect();
    expect($offerLetter->refresh()->isAccepted())->toBeTrue();
});

it('staff can verify a required document on conditional offer via web', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'conditional' => true,
            'required_documents' => ['marksheet'],
            'document_verification_status' => ['marksheet' => false],
        ]);
    $this->actingAs($this->user);
    $response = $this->post(
        route('crm.offer_letters.documents.verify', [$offerLetter->uuid, 'marksheet']),
        ['verified' => true]
    );
    $response->assertRedirect();
    expect($offerLetter->refresh()->document_verification_status['marksheet'])->toBeTrue();
});

it('api can verify a required document on conditional offer', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'conditional' => true,
            'required_documents' => ['id_proof'],
            'document_verification_status' => ['id_proof' => false],
        ]);
    $response = $this->actingAs($this->user)
        ->patchJson(
            route('api.v1.crm.offers.documents.verify', [$offerLetter->uuid, 'id_proof']),
            ['verified' => true]
        );
    $response->assertOk();
    expect($offerLetter->refresh()->document_verification_status['id_proof'])->toBeTrue();
});

// AP-015: Student portal acceptance tests

it('staff can generate a portal link for the student', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'expires_at' => now()->addDays(30),
        ]);
    $this->actingAs($this->user);
    $response = $this->postJson(
        route('api.v1.crm.offers.portal_link', $offerLetter->uuid)
    );
    $response->assertOk();
    $response->assertJsonStructure(['success', 'data' => ['portal_url', 'token', 'expires_in_hours']]);
    $offerLetter->refresh();
    expect($offerLetter->acceptance_token)->not->toBeNull()
        ->and($offerLetter->acceptance_token_expires_at)->not->toBeNull();
});

it('applicant can view offer via public portal token', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'expires_at' => now()->addDays(30),
            'acceptance_token' => 'test-valid-token-64chars-padded-to-meet-the-length-requirement-abc',
            'acceptance_token_expires_at' => now()->addHours(72),
        ]);
    $response = $this->get(route('portal.offers.show', 'test-valid-token-64chars-padded-to-meet-the-length-requirement-abc'));
    $response->assertOk();
    $response->assertSee($this->lead->full_name);
});

it('applicant can accept offer via portal token', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'expires_at' => now()->addDays(30),
            'acceptance_token' => 'accept-valid-token-64chars-padded-to-meet-the-length-requirement-x',
            'acceptance_token_expires_at' => now()->addHours(72),
        ]);
    $response = $this->post(
        route('portal.offers.accept', 'accept-valid-token-64chars-padded-to-meet-the-length-requirement-x'),
        ['notes' => 'Happy to accept']
    );
    $response->assertRedirect();
    expect($offerLetter->refresh()->isAccepted())->toBeTrue()
        ->and($offerLetter->acceptance_ip)->not->toBeNull();
});

it('applicant can decline offer via portal token', function () {
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'expires_at' => now()->addDays(30),
            'acceptance_token' => 'decline-valid-token-64chars-padded-to-meet-the-length-requirement-',
            'acceptance_token_expires_at' => now()->addHours(72),
        ]);
    $response = $this->post(
        route('portal.offers.decline', 'decline-valid-token-64chars-padded-to-meet-the-length-requirement-'),
        ['reason' => 'Accepted another offer']
    );
    $response->assertRedirect();
    expect($offerLetter->refresh()->isDeclined())->toBeTrue()
        ->and($offerLetter->decline_reason)->toBe('Accepted another offer');
});

it('portal returns 410 for expired token', function () {
    OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'status' => 'generated',
            'acceptance_token' => 'expired-token-64chars-padded-to-meet-the-length-requirement-abcde',
            'acceptance_token_expires_at' => now()->subHours(1),
        ]);
    $response = $this->get(route('portal.offers.show', 'expired-token-64chars-padded-to-meet-the-length-requirement-abcde'));
    $response->assertStatus(410);
});

it('offer letter template merge tags are replaced', function () {
    $template = OfferLetterTemplate::factory()->create([
        'institution_id' => 1,
        'html_template' => <<<HTML
            <html>
                <body>
                    <p>Dear {{lead.first_name}},</p>
                    <p>Your application for {{application.programme_name}} has been selected.</p>
                    <p>Offer expires on {{offer.expires_on}}.</p>
                </body>
            </html>
        HTML,
    ]);
    $offerLetter = OfferLetter::factory()
        ->for($this->application)
        ->for($this->lead)
        ->create([
            'institution_id' => 1,
            'expires_at' => now()->addDays(30),
        ]);
    $renderService = app(OfferLetterRenderService::class);
    $html = $renderService->renderTemplate($template, $this->lead, $this->application, $offerLetter);
    expect($html)->toContain($this->lead->first_name)
        ->toContain($this->programme->name)
        ->not->toContain('{{'); // All merge tags replaced
});
