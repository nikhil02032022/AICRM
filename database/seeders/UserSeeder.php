<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CRM\Campus;
use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * UserSeeder — Seeds one test institution + campus + one user per BRD role.
 *
 * Test credentials (all passwords: password):
 *   superadmin@a2acrm.test          → super-admin
 *   admin@demo.edu                  → institution-admin
 *   director@demo.edu               → admissions-director
 *   manager@demo.edu                → admissions-manager
 *   sr.counsellor@demo.edu          → senior-counsellor
 *   jr.counsellor@demo.edu          → junior-counsellor
 *   marketing@demo.edu              → marketing-manager
 *   finance@demo.edu                → finance-officer
 *   docverifier@demo.edu            → document-verifier
 *   agent@demo.edu                  → agent
 *   applicant@demo.edu              → applicant
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ----------------------------------------------------------------
        // Demo Institution + Campus
        // ----------------------------------------------------------------
        $institution = Institution::firstOrCreate(
            ['code' => 'DEMO'],
            [
                'name'      => 'Demo University',
                'domain'    => 'demo.edu',
                'is_active' => true,
                'settings'  => [
                    'primary_color'     => '#2563eb',
                    'admissions_email'  => 'admissions@demo.edu',
                ],
            ]
        );

        $campus = Campus::firstOrCreate(
            ['institution_id' => $institution->id, 'code' => 'MAIN'],
            [
                'name'      => 'Main Campus',
                'city'      => 'Bengaluru',
                'state'     => 'Karnataka',
                'is_active' => true,
            ]
        );

        // ----------------------------------------------------------------
        // One user per BRD role (§12)
        // ----------------------------------------------------------------
        $users = [
            [
                'name'  => 'Super Admin',
                'email' => 'superadmin@a2acrm.test',
                'role'  => 'super-admin',
                'institution_id' => null,  // MEETCS-level, no institution
                'campus_id'      => null,
            ],
            [
                'name'  => 'Institution Admin',
                'email' => 'admin@demo.edu',
                'role'  => 'institution-admin',
                'institution_id' => $institution->id,
                'campus_id'      => $campus->id,
            ],
            [
                'name'  => 'Admissions Director',
                'email' => 'director@demo.edu',
                'role'  => 'admissions-director',
                'institution_id' => $institution->id,
                'campus_id'      => $campus->id,
            ],
            [
                'name'  => 'Admissions Manager',
                'email' => 'manager@demo.edu',
                'role'  => 'admissions-manager',
                'institution_id' => $institution->id,
                'campus_id'      => $campus->id,
            ],
            [
                'name'  => 'Senior Counsellor',
                'email' => 'sr.counsellor@demo.edu',
                'role'  => 'senior-counsellor',
                'institution_id' => $institution->id,
                'campus_id'      => $campus->id,
            ],
            [
                'name'  => 'Junior Counsellor',
                'email' => 'jr.counsellor@demo.edu',
                'role'  => 'junior-counsellor',
                'institution_id' => $institution->id,
                'campus_id'      => $campus->id,
            ],
            [
                'name'  => 'Marketing Manager',
                'email' => 'marketing@demo.edu',
                'role'  => 'marketing-manager',
                'institution_id' => $institution->id,
                'campus_id'      => $campus->id,
            ],
            [
                'name'  => 'Finance Officer',
                'email' => 'finance@demo.edu',
                'role'  => 'finance-officer',
                'institution_id' => $institution->id,
                'campus_id'      => $campus->id,
            ],
            [
                'name'  => 'Document Verifier',
                'email' => 'docverifier@demo.edu',
                'role'  => 'document-verifier',
                'institution_id' => $institution->id,
                'campus_id'      => $campus->id,
            ],
            [
                'name'  => 'Channel Agent',
                'email' => 'agent@demo.edu',
                'role'  => 'agent',
                'institution_id' => $institution->id,
                'campus_id'      => $campus->id,
            ],
            [
                'name'  => 'Test Applicant',
                'email' => 'applicant@demo.edu',
                'role'  => 'applicant',
                'institution_id' => $institution->id,
                'campus_id'      => $campus->id,
            ],
        ];

        foreach ($users as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'           => $data['name'],
                    'password'       => bcrypt('password'),
                    'institution_id' => $data['institution_id'],
                    'campus_id'      => $data['campus_id'],
                    'is_active'      => true,
                    'email_verified_at' => now(),
                ]
            );

            // Assign role (sync — removes old roles)
            $user->syncRoles([$data['role']]);
        }

        $this->command->info('✅ 11 BRD test users seeded (password: password)');
        $this->command->table(
            ['Role', 'Email'],
            collect($users)->map(fn ($u) => [$u['role'], $u['email']])->toArray()
        );
    }
}
