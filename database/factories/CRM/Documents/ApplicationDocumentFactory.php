<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Documents;

use App\Enums\CRM\Documents\DocumentStatus;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Documents\DocumentChecklistItem;
use App\Models\CRM\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ApplicationDocument> */
class ApplicationDocumentFactory extends Factory
{
    protected $model = ApplicationDocument::class;

    public function definition(): array
    {
        return [
            'institution_id'              => Institution::factory(),
            'application_uuid'            => Str::uuid(),
            'lead_uuid'                   => Str::uuid(),
            'document_checklist_item_id'  => DocumentChecklistItem::factory(),
            'status'                      => DocumentStatus::NOT_SUBMITTED->value,
            'version'                     => 1,
        ];
    }
}
