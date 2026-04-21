<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Models\CRM\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'code' => strtoupper(Str::random(6)),
            'domain' => fake()->domainName(),
            'is_active' => true,
        ];
    }
}
