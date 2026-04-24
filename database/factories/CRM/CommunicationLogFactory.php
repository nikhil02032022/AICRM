<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Enums\CRM\CommunicationChannel;
use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Models\CRM\CommunicationLog;
use App\Models\CRM\Institution;
use App\Models\CRM\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationLog>
 */
class CommunicationLogFactory extends Factory
{
    protected $model = CommunicationLog::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'lead_id'        => Lead::factory(),
            'channel'        => $this->faker->randomElement(CommunicationChannel::cases())->value,
            'direction'      => $this->faker->randomElement(MessageDirection::cases())->value,
            'status'         => MessageStatus::DELIVERED->value,
            'body_preview'   => $this->faker->sentence(),
        ];
    }

    public function inbound(): static
    {
        return $this->state(['direction' => MessageDirection::INBOUND->value]);
    }

    public function outbound(): static
    {
        return $this->state(['direction' => MessageDirection::OUTBOUND->value]);
    }
}
