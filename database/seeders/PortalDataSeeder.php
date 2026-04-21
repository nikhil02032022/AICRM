<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CRM\ApplicationStatus;
use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Models\CRM\Application;
use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * PortalDataSeeder — Seeds a demo applicant lead + 2 applications so the
 * student portal dashboard shows real data instead of the "No applications" empty state.
 *
 * Portal login: student@demo.edu  (OTP is printed to log/console in local env)
 * Visit: /portal/auth/login?institution={DEMO institution UUID}
 */
class PortalDataSeeder extends Seeder
{
    public function run(): void
    {
        $institution = Institution::withoutGlobalScopes()
            ->where('code', 'DEMO')
            ->firstOrFail();

        // ---------------------------------------------------------------
        // Programmes
        // ---------------------------------------------------------------
        $mba = CrmProgramme::withoutGlobalScopes()->firstOrCreate(
            ['institution_id' => $institution->id, 'code' => 'MBA-GM'],
            [
                'name'             => 'MBA – General Management',
                'level'            => 'postgraduate',
                'department'       => 'Business School',
                'intake_capacity'  => 120,
                'is_active'        => true,
            ]
        );

        $btech = CrmProgramme::withoutGlobalScopes()->firstOrCreate(
            ['institution_id' => $institution->id, 'code' => 'BTECH-CS'],
            [
                'name'             => 'B.Tech – Computer Science',
                'level'            => 'undergraduate',
                'department'       => 'Engineering',
                'intake_capacity'  => 180,
                'is_active'        => true,
            ]
        );

        // ---------------------------------------------------------------
        // Demo lead (the portal "student" account)
        // email is encrypted at rest — cannot use SQL match; scan in PHP instead
        // ---------------------------------------------------------------
        $targetEmail = 'student@demo.edu';
        $lead = Lead::withoutGlobalScopes()
            ->where('institution_id', $institution->id)
            ->whereNull('deleted_at')
            ->get()
            ->first(fn (Lead $l) => strtolower(trim($l->email ?? '')) === $targetEmail);

        if ($lead === null) {
            $lead = Lead::withoutGlobalScopes()->create([
                'institution_id'    => $institution->id,
                'email'             => $targetEmail,
                'first_name'        => 'Demo',
                'last_name'         => 'Student',
                'mobile'            => '+919876543210',
                'source'            => LeadSource::WEBSITE_ORGANIC->value,
                'status'            => LeadStatus::APPLICATION_SUBMITTED->value,
                'consent_given'     => true,
                'consent_timestamp' => now(),
                'opt_out'           => false,
            ]);
        }

        // ---------------------------------------------------------------
        // Applications
        // ---------------------------------------------------------------
        Application::withoutGlobalScopes()->firstOrCreate(
            ['lead_uuid' => $lead->uuid, 'programme_id' => $mba->id],
            [
                'institution_id'             => $institution->id,
                'application_form_draft_uuid' => Str::uuid()->toString(),
                'status'                     => ApplicationStatus::OFFER_ISSUED->value,
                'submitted_at'               => now()->subDays(15),
                'stage_entered_at'           => now()->subDays(5),
            ]
        );

        Application::withoutGlobalScopes()->firstOrCreate(
            ['lead_uuid' => $lead->uuid, 'programme_id' => $btech->id],
            [
                'institution_id'             => $institution->id,
                'application_form_draft_uuid' => Str::uuid()->toString(),
                'status'                     => ApplicationStatus::UNDER_REVIEW->value,
                'submitted_at'               => now()->subDays(3),
                'stage_entered_at'           => now()->subDays(3),
            ]
        );

        $this->command->info('✅ Portal demo data seeded.');
        $this->command->info("   Lead email : student@demo.edu");
        $this->command->info("   Institution UUID: {$institution->uuid}");
        $this->command->info("   Portal login: /portal/auth/login?institution={$institution->uuid}");
    }
}
