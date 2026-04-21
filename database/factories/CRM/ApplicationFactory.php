<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Enums\CRM\ApplicationStatus;
use App\Models\CRM\Application;
use App\Models\CRM\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'lead_uuid' => Str::uuid(),
            'application_form_draft_uuid' => Str::uuid(),
            'status' => ApplicationStatus::UNDER_REVIEW->value,
            'submitted_at' => now(),
        ];
    }
}
