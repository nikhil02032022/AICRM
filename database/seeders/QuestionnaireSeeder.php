<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CRM\QuestionnaireStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\QualificationQuestionnaire;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

// BRD: CRM-LQ-009 — Seed starter qualification questionnaires for immediate usability
class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = Institution::query()->where('is_active', true)->get();

        foreach ($institutions as $institution) {
            $createdBy = User::query()
                ->where('institution_id', $institution->id)
                ->value('id');

            QualificationQuestionnaire::withoutGlobalScopes()->updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'name' => 'BANT Qualification',
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'campus_id' => null,
                    'status' => QuestionnaireStatus::ACTIVE->value,
                    'questions' => [
                        [
                            'key' => 'budget_range',
                            'label' => 'Budget Range',
                            'type' => 'select',
                            'required' => true,
                            'options' => ['below_2_lakh', '2_to_5_lakh', 'above_5_lakh'],
                        ],
                        [
                            'key' => 'decision_maker',
                            'label' => 'Decision Maker Identified',
                            'type' => 'boolean',
                            'required' => true,
                        ],
                        [
                            'key' => 'joining_timeline',
                            'label' => 'Expected Joining Timeline',
                            'type' => 'text',
                            'required' => true,
                        ],
                    ],
                    'created_by' => $createdBy,
                ],
            );

            QualificationQuestionnaire::withoutGlobalScopes()->updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'name' => 'Programme Fit Check',
                ],
                [
                    'uuid' => (string) Str::uuid(),
                    'campus_id' => null,
                    'status' => QuestionnaireStatus::ACTIVE->value,
                    'questions' => [
                        [
                            'key' => 'programme_clarity',
                            'label' => 'Programme Preference is Clear',
                            'type' => 'boolean',
                            'required' => true,
                        ],
                        [
                            'key' => 'career_goal',
                            'label' => 'Career Goal Alignment Notes',
                            'type' => 'text',
                            'required' => false,
                        ],
                    ],
                    'created_by' => $createdBy,
                ],
            );
        }

        $this->command->info('✅ Starter qualification questionnaires seeded.');
    }
}
