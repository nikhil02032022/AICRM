<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Enums\CRM\LeadSource;
use App\Enums\CRM\LeadStatus;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'mobile' => fake()->numerify('+91##########'),
            'email' => fake()->unique()->safeEmail(),
            'source' => fake()->randomElement(LeadSource::cases())->value,
            'status' => LeadStatus::NEW_ENQUIRY->value,
            'consent_given' => true,
            'consent_timestamp' => now(),
            'opt_out' => false,
        ];
    }
}
