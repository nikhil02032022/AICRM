<?php

declare(strict_types=1);

namespace App\Events\CRM\Counselling;

use App\Models\CRM\WalkInToken;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-EC-019 — Broadcast on walk-in.{campus_id} for any token status change (issue, serve, skip)
final class WalkInTokenStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly WalkInToken $token,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("walk-in.{$this->token->campus_id}");
    }

    public function broadcastAs(): string
    {
        return 'token.status_changed';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'token_number' => $this->token->token_number,
            'status' => $this->token->status->value,
            'status_label' => $this->token->status->label(),
            'badge_colour' => $this->token->status->badgeColour(),
        ];
    }
}
