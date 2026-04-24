<?php

declare(strict_types=1);

namespace App\Events\CRM\Counselling;

use App\Models\CRM\WalkInToken;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// BRD: CRM-EC-019 — Broadcast on walk-in.{campus_id} when counsellor calls the next token
// Public channel (no auth) so the reception display screen can subscribe without login
final class WalkInTokenCalled implements ShouldBroadcast
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
        return 'token.called';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'token_number' => $this->token->token_number,
            'status' => $this->token->status->value,
            'status_label' => $this->token->status->label(),
        ];
    }
}
