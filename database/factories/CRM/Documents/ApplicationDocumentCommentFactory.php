<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Documents;

use App\Enums\CRM\Documents\DocumentCommentType;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Documents\ApplicationDocumentComment;
use App\Models\CRM\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ApplicationDocumentComment> */
class ApplicationDocumentCommentFactory extends Factory
{
    protected $model = ApplicationDocumentComment::class;

    public function definition(): array
    {
        return [
            'institution_id'         => Institution::factory(),
            'application_document_id'=> ApplicationDocument::factory(),
            'actor_id'               => User::factory(),
            'type'                   => DocumentCommentType::INTERNAL->value,
            'comment'                => 'Needs clearer scan.',
        ];
    }
}
