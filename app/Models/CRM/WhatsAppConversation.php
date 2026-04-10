<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\ConversationStatus;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

// BRD: CRM-CC-010, CRM-CC-012 — WhatsApp BSP conversation (shared inbox)
// BRD: CRM-LC-007 — Auto-created from inbound Click-to-Chat
#[ObservedBy(AuditObserver::class)]
class WhatsAppConversation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'whatsapp_conversations';

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'lead_id',
        'bsp_conversation_id',
        'wa_phone_number',
        'wa_display_name',
        'assigned_user_id',
        'status',
        'last_message_at',
        'is_bot_active',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status'          => ConversationStatus::class,
            'last_message_at' => 'datetime',
            'is_bot_active'   => 'boolean',
        ];
    }

    // BRD: CRM-CR-006 — PII encrypted at rest (DPDP)
    public function getWaPhoneNumberAttribute(string $value): string
    {
        return Crypt::decryptString($value);
    }

    public function setWaPhoneNumberAttribute(string $value): void
    {
        $this->attributes['wa_phone_number'] = Crypt::encryptString($value);
    }

    public function getWaDisplayNameAttribute(?string $value): ?string
    {
        return $value !== null ? Crypt::decryptString($value) : null;
    }

    public function setWaDisplayNameAttribute(?string $value): void
    {
        $this->attributes['wa_display_name'] = $value !== null ? Crypt::encryptString($value) : null;
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id')->orderBy('created_at');
    }
}
