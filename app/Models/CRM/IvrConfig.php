<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\TelephonyProvider;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

// BRD: CRM-CC-019 — IVR configuration per institution/campus
// BRD: CRM-LC-010 — Inbound IVR calls auto-create leads
#[ObservedBy(AuditObserver::class)]
class IvrConfig extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'ivr_configs';

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
        'provider',
        'virtual_number',
        'welcome_message',
        'collect_name',
        'collect_programme',
        'fallback_counsellor_id',
        'is_active',
        'credentials_id',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'provider'          => TelephonyProvider::class,
            'collect_name'      => 'boolean',
            'collect_programme' => 'boolean',
            'is_active'         => 'boolean',
        ];
    }

    // BRD: CRM-CR-006 — Virtual number encrypted at rest (DPDP)
    public function getVirtualNumberAttribute(string $value): string
    {
        return Crypt::decryptString($value);
    }

    public function setVirtualNumberAttribute(string $value): void
    {
        $this->attributes['virtual_number'] = Crypt::encryptString($value);
    }

    public function fallbackCounsellor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fallback_counsellor_id');
    }

    public function credentials(): BelongsTo
    {
        return $this->belongsTo(IntegrationCredential::class, 'credentials_id');
    }
}
