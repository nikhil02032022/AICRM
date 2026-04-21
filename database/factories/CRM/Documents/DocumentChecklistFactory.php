<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Documents;

use App\Models\CRM\Documents\DocumentChecklist;
use App\Models\CRM\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentChecklist> */
class DocumentChecklistFactory extends Factory
{
    protected $model = DocumentChecklist::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'name'           => 'Admission Checklist',
            'is_active'      => true,
        ];
    }
}
