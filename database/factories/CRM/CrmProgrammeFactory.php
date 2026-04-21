<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Models\CRM\CrmProgramme;
use App\Models\CRM\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmProgramme>
 */
class CrmProgrammeFactory extends Factory
{
    protected $model = CrmProgramme::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'name' => fake()->randomElement(['MBA', 'BCA', 'MCA', 'B.Tech', 'M.Tech', 'BBA', 'B.Sc', 'M.Sc']),
            'code' => strtoupper(fake()->lexify('???')),
            'level' => fake()->randomElement(['UG', 'PG', 'Diploma']),
            'department' => fake()->randomElement(['Management', 'Technology', 'Science', 'Commerce']),
            'is_active' => true,
        ];
    }
}
