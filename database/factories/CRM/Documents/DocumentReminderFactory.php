<?php

declare(strict_types=1);

namespace Database\Factories\CRM\Documents;

use App\Enums\CRM\Documents\DocumentReminderStatus;
use App\Enums\CRM\Payments\PaymentChannel;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Documents\DocumentReminder;
use App\Models\CRM\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentReminder> */
class DocumentReminderFactory extends Factory
{
    protected $model = DocumentReminder::class;

    public function definition(): array
    {
        return [
            'institution_id'           => Institution::factory(),
            'application_document_id'  => ApplicationDocument::factory(),
            'scheduled_for'            => now()->addDay(),
            'channel'                  => PaymentChannel::EMAIL->value,
            'status'                   => DocumentReminderStatus::PENDING->value,
            'opted_out'                => false,
        ];
    }
}
