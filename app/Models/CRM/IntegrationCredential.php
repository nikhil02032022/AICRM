<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\IntegrationChannel;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-SA-010 — Integration credential management with AES-256 encrypted credentials column
// OWASP A05: credentials JSON is encrypted at rest; never exposed in API responses or logs
#[ObservedBy(AuditObserver::class)]
class IntegrationCredential extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'integration_credentials';

    /**
     * HasUuids targets only the 'uuid' column — keeping 'id' as auto-increment bigint.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    // BRD: NFR-MT-001 — InstitutionScope enforces multi-tenant isolation
    protected static function booted(): void
    {
        static::addGlobalScope(new InstitutionScope);
    }

    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'institution_id',
        'campus_id',
        'channel',
        'label',
        'credentials',
        'is_active',
        'last_used_at',
    ];

    /**
     * OWASP A05 / BRD: CRM-SA-010 — credentials column encrypted AES-256 at app layer.
     * Also excluded from serialisation via $hidden.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'channel' => IntegrationChannel::class,
            'credentials' => 'encrypted:array',  // AES-256 + json_decode on read
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * BRD: CRM-SA-010 / OWASP A05 — never expose raw credentials in serialisation.
     *
     * @var list<string>
     */
    protected $hidden = ['credentials'];

    // -----------------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------------

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Safely retrieve a single credential key from the encrypted JSON.
     * Returns null if the key does not exist — never throws.
     */
    public function getCredential(string $key): ?string
    {
        $creds = $this->credentials ?? [];

        return isset($creds[$key]) ? (string) $creds[$key] : null;
    }

    /** Mark this credential as recently used without triggering the AuditObserver. */
    public function touchLastUsed(): void
    {
        $this->timestamps = false;
        $this->update(['last_used_at' => now()]);
        $this->timestamps = true;
    }
}
