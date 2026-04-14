<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CRM\BadgeCategory;
use App\Models\CRM\Badge;
use Illuminate\Database\Seeder;

/**
 * BRD: CRM-EC-010 — Seed default gamification badges
 */
class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // Performance Badges
            [
                'name' => 'First Conversion',
                'slug' => 'first-conversion',
                'description' => 'Congratulations on your first successful lead conversion!',
                'icon' => '🎯',
                'color' => 'blue',
                'category' => BadgeCategory::PERFORMANCE,
                'criteria' => ['leads_converted' => 1],
                'points' => 100,
            ],
            [
                'name' => 'Top Converter',
                'slug' => 'top-converter',
                'description' => 'Achieved 10 conversions in a single period',
                'icon' => '⭐',
                'color' => 'yellow',
                'category' => BadgeCategory::PERFORMANCE,
                'criteria' => ['leads_converted' => 10],
                'points' => 500,
            ],
            [
                'name' => 'Conversion Master',
                'slug' => 'conversion-master',
                'description' => 'Achieved 50 conversions - you\'re a master!',
                'icon' => '👑',
                'color' => 'purple',
                'category' => BadgeCategory::EXCELLENCE,
                'criteria' => ['leads_converted' => 50],
                'points' => 2000,
            ],

            // Milestone Badges
            [
                'name' => 'Century Club',
                'slug' => 'century-club',
                'description' => 'Handled 100 leads in a single period',
                'icon' => '💯',
                'color' => 'indigo',
                'category' => BadgeCategory::MILESTONE,
                'criteria' => ['leads_handled' => 100],
                'points' => 300,
            ],
            [
                'name' => 'Call Champion',
                'slug' => 'call-champion',
                'description' => 'Made 100 calls in a single period',
                'icon' => '📞',
                'color' => 'green',
                'category' => BadgeCategory::MILESTONE,
                'criteria' => ['calls_made' => 100],
                'points' => 200,
            ],

            // Consistency Badges
            [
                'name' => 'Week Warrior',
                'slug' => 'week-warrior',
                'description' => 'Maintained a 7-day active streak',
                'icon' => '🔥',
                'color' => 'red',
                'category' => BadgeCategory::CONSISTENCY,
                'criteria' => ['streak_days' => 7],
                'points' => 250,
            ],
            [
                'name' => 'Month Maven',
                'slug' => 'month-maven',
                'description' => 'Maintained a 30-day active streak',
                'icon' => '🚀',
                'color' => 'orange',
                'category' => BadgeCategory::CONSISTENCY,
                'criteria' => ['streak_days' => 30],
                'points' => 1000,
            ],

            // Excellence Badges
            [
                'name' => 'High Rate Achiever',
                'slug' => 'high-rate-achiever',
                'description' => 'Achieved 25% or higher conversion rate',
                'icon' => '🎖️',
                'color' => 'teal',
                'category' => BadgeCategory::EXCELLENCE,
                'criteria' => ['conversion_rate' => 25.0],
                'points' => 750,
            ],
            [
                'name' => 'Speed Demon',
                'slug' => 'speed-demon',
                'description' => 'Average response time under 10 minutes',
                'icon' => '⚡',
                'color' => 'yellow',
                'category' => BadgeCategory::EXCELLENCE,
                'criteria' => ['avg_response_time_minutes' => 10],
                'points' => 400,
            ],
            [
                'name' => '5-Star Counsellor',
                'slug' => 'five-star-counsellor',
                'description' => 'Student satisfaction score of 4.5 or higher',
                'icon' => '🌟',
                'color' => 'amber',
                'category' => BadgeCategory::EXCELLENCE,
                'criteria' => ['student_satisfaction_score' => 4.5],
                'points' => 600,
            ],

            // Special Badges
            [
                'name' => 'Email Expert',
                'slug' => 'email-expert',
                'description' => 'Sent 50 emails in a single period',
                'icon' => '📧',
                'color' => 'cyan',
                'category' => BadgeCategory::SPECIAL,
                'criteria' => ['emails_sent' => 50],
                'points' => 150,
            ],
            [
                'name' => 'Meeting Maestro',
                'slug' => 'meeting-maestro',
                'description' => 'Scheduled 20 meetings in a single period',
                'icon' => '📅',
                'color' => 'lime',
                'category' => BadgeCategory::SPECIAL,
                'criteria' => ['meetings_scheduled' => 20],
                'points' => 300,
            ],
            [
                'name' => 'Application Ace',
                'slug' => 'application-ace',
                'description' => 'Submitted 10 applications in a single period',
                'icon' => '📝',
                'color' => 'pink',
                'category' => BadgeCategory::SPECIAL,
                'criteria' => ['applications_submitted' => 10],
                'points' => 400,
            ],
        ];

        foreach ($badges as $badgeData) {
            Badge::updateOrCreate(
                ['slug' => $badgeData['slug']],
                $badgeData
            );
        }

        $this->command->info('✓ Gamification badges seeded successfully');
    }
}
