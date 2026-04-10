<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\MessageDirection;
use App\Enums\CRM\MessageStatus;
use App\Enums\CRM\WaMessageType;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

// BRD: CRM-CC-014 — WhatsApp message level events (delivery, read, reply)
// Immutable — no soft deletes
class WhatsAppMessage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'whatsapp_messages';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'conversation_id',
        'institution_id',
        'bsp_message_id',
        'direction',
        'message_type',
        'body',
        'template_name',
        'media_url',
        'status',
        'delivered_at',
        'read_at',
        'sent_by',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'direction'    => MessageDirection::class,
            'message_type' => WaMessageType::class,
            'status'       => MessageStatus::class,
            'delivered_at' => 'datetime',
            'read_at'      => 'datetime',
        ];
    }

    // BRD: CRM-CR-006 — Encrypt message body (may contain PII) at rest
    public function getBodyAttribute(?string $value): ?string
    {
        return $value !== null ? Crypt::decryptString($value) : null;
    }

    public function setBodyAttribute(?string $value): void
    {
        $this->attributes['body'] = $value !== null ? Crypt::encryptString($value) : null;
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
