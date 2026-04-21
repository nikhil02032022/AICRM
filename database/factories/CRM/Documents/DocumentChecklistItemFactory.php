<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Documents;

use App\Models\CRM\Documents\DocumentChecklist;
use App\Models\CRM\Documents\DocumentChecklistItem;
use App\Models\CRM\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<DocumentChecklistItem> */
class DocumentChecklistItemFactory extends Factory
{
    protected $model = DocumentChecklistItem::class;

    public function definition(): array
    {
        return [
            'institution_id'         => Institution::factory(),
            'document_checklist_id'  => DocumentChecklist::factory(),
            'code'                   => Str::upper(Str::random(6)),
            'label'                  => '10th Marksheet',
            'is_mandatory'           => true,
            'max_size_kb'            => 2048,
            'allowed_mime'           => ['application/pdf', 'image/jpeg', 'image/png'],
            'sort_order'             => 0,
        ];
    }
}
