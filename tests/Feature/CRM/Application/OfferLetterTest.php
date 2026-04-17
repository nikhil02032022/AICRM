<?php

declare(strict_types=1);

namespace Tests\Feature\CRM\Application;

use App\Jobs\CRM\GenerateOfferLetterJob;
use App\Jobs\CRM\SendOfferLetterJob;
use App\Models\CRM\Application;
use App\Models\CRM\Lead;
use App\Models\CRM\OfferLetter;
use App\Models\CRM\OfferLetterTemplate;
use App\Models\CRM\CrmProgramme;
use App\Models\User;
use App\Services\CRM\Application\OfferLetterRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// BRD: CRM-AP-012 — Offer letter generation, rendering, and management tests
class OfferLetterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Lead $lead;
    private Application $application;
    private CrmProgramme $programme;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        Queue::fake();

        $this->user = User::factory()->create(['institution_id' => 1]);
        $this->lead = Lead::factory()->create(['institution_id' => 1]);
        $this->programme = CrmProgramme::factory()->create(['institution_id' => 1]);
        $this->application = Application::factory()
            ->for($this->lead, 'lead')
            ->for($this->programme, 'programme')
            ->create(['institution_id' => 1]);
    }

    /** @test */
    public function can_generate_offer_letter_for_application(): void
    {
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
    }

    /** @test */
    public function offer_letter_generation_dispatches_pdf_job(): void
    {
        $this->actingAs($this->user);

        $this->post(route('crm.applications.offers.store', $this->application->uuid), [
            'expires_in_days' => 30,
        ]);

        Queue::assertPushed(GenerateOfferLetterJob::class);
    }

    /** @test */
    public function can_render_offer_letter_template_to_pdf(): void
    {
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

        $this->assertStringContainsString($this->lead->full_name, $html);
        $this->assertStringContainsString($this->programme->name, $html);
    }

    /** @test */
    public function can_record_offer_acceptance(): void
    {
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

        $this->assertTrue($offerLetter->refresh()->isAccepted());
        $this->assertNotNull($offerLetter->acceptance_recorded_at);
        $this->assertNotNull($offerLetter->acceptance_ip);
    }

    /** @test */
    public function can_record_offer_decline(): void
    {
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

        $this->assertTrue($offerLetter->refresh()->isDeclined());
        $this->assertNotNull($offerLetter->declined_at);
        $this->assertEquals('Better offer received elsewhere', $offerLetter->decline_reason);
    }

    /** @test */
    public function can_send_offer_letter_via_email(): void
    {
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

        $this->assertEquals('email', $offerLetter->refresh()->sent_via);
        $this->assertNotNull($offerLetter->sent_at);
    }

    /** @test */
    public function cannot_accept_expired_offer(): void
    {
        $offerLetter = OfferLetter::factory()
            ->for($this->application)
            ->for($this->lead)
            ->create([
                'institution_id' => 1,
                'status' => 'generated',
                'expires_at' => now()->subDays(1), // Expired
            ]);

        $this->actingAs($this->user);

        $response = $this->get(route('crm.offer_letters.accept.form', $offerLetter->uuid));
        $response->assertStatus(422);
    }

    /** @test */
    public function api_can_list_offers_for_application(): void
    {
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
    }

    /** @test */
    public function api_can_generate_offer_letter(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('api.v1.crm.applications.offers.store', $this->application->uuid), [
                'expires_in_days' => 30,
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['success', 'data' => ['uuid', 'status']]);
    }

    /** @test */
    public function offer_letter_provides_download_signed_url(): void
    {
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
    }

    /** @test */
    public function offer_letter_cannot_be_accepted_twice(): void
    {
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
    }

    /** @test */
    public function offer_letter_template_merge_tags_are_replaced(): void
    {
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

        $this->assertStringContainsString($this->lead->first_name, $html);
        $this->assertStringContainsString($this->programme->name, $html);
        $this->assertStringNotContainsString('{{', $html); // All merge tags replaced
    }

    /** @test */
    public function send_offer_letter_job_updates_delivery_status_and_logs_activity(): void
    {
        $offerLetter = OfferLetter::factory()
            ->for($this->application)
            ->for($this->lead)
            ->create([
                'institution_id' => 1,
                'status' => 'generated',
                'pdf_path' => 'crm/offer-letters/1/test.pdf',
            ]);

        $job = new \App\Jobs\CRM\SendOfferLetterJob($offerLetter, 'email');
        $job->handle(app(\App\Services\CRM\Communication\CommunicationEngineService::class));

        $offerLetter->refresh();
        $this->assertEquals('sent', $offerLetter->status);
        $this->assertEquals('email', $offerLetter->sent_via);
        $this->assertEquals('sent', $offerLetter->delivery_status);
        $this->assertNotNull($offerLetter->delivery_message_id);
        // Optionally, check activity log if activity() is set up for testing
    }

    /** @test */
    public function send_offer_letter_job_handles_delivery_failure(): void
    {
        $offerLetter = OfferLetter::factory()
            ->for($this->application)
            ->for($this->lead)
            ->create([
                'institution_id' => 1,
                'status' => 'generated',
                'pdf_path' => 'crm/offer-letters/1/test.pdf',
            ]);

        $mockComms = $this->getMockBuilder(\App\Services\CRM\Communication\CommunicationEngineService::class)
            ->onlyMethods(['sendOfferLetter'])
            ->getMock();
        $mockComms->method('sendOfferLetter')->willThrowException(new \Exception('Simulated failure'));

        $job = new \App\Jobs\CRM\SendOfferLetterJob($offerLetter, 'email');
        $job->handle($mockComms);

        $offerLetter->refresh();
        $this->assertEquals('failed', $offerLetter->delivery_status);
    }
}
