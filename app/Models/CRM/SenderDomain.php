<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\EmailProvider;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Models\User;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-CC-004 — Custom sender domain with SPF/DKIM/DMARC verification
#[ObservedBy(AuditObserver::class)]
class SenderDomain extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'sender_domains';

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
        'domain',
        'default_from_name',
        'default_from_email',
        'spf_verified',
        'dkim_verified',
        'dmarc_verified',
        'provider',
        'credentials_id',
        'is_default',
        'verified_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'provider'      => EmailProvider::class,
            'spf_verified'  => 'boolean',
            'dkim_verified' => 'boolean',
            'dmarc_verified'=> 'boolean',
            'is_default'    => 'boolean',
            'verified_at'   => 'datetime',
        ];
    }

    public function isFullyVerified(): bool
    {
        return $this->spf_verified && $this->dkim_verified && $this->dmarc_verified;
    }

    public function credentials(): BelongsTo
    {
        return $this->belongsTo(IntegrationCredential::class, 'credentials_id');
    }
}
