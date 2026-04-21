<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Models\CRM\Application;
use App\Models\CRM\ApplicationConversionLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApplicationConversionLog>
 */
class ApplicationConversionLogFactory extends Factory
{
    protected $model = ApplicationConversionLog::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'application_uuid' => Str::uuid(),
            'lead_uuid' => Str::uuid(),
            'status' => 'success',
            'attempted_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ];
    }
}
