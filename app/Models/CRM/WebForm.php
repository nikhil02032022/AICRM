<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\LeadSource;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-LC-001 — WebForm entity for configurable, embeddable lead capture forms
// BRD: CRM-LC-002 — Fields JSON supports conditional logic rules (show_if schema)
// BRD: CRM-LC-009 — QR code support via slug + UTM-encoded public URL
// BRD: CRM-CR-002 — consent_form_version tracked per form
#[ObservedBy(AuditObserver::class)]
class WebForm extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'web_forms';

    /**
     * HasUuids targets the 'uuid' column only.
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
        'name',
        'slug',
        'fields',
        'is_active',
        'embed_token',
        'source',
        'redirect_url',
        'consent_form_version',
        'accent_color',
        'logo_url',
        'custom_css',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'is_active' => 'boolean',
            'source' => LeadSource::class,
        ];
    }

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
    // URL helpers
    // -----------------------------------------------------------------------

    /** BRD: CRM-LC-001 — Public URL for the web enquiry form */
    public function publicUrl(): string
    {
        return url('/f/'.$this->slug);
    }

    /** BRD: CRM-LC-001 — iFrame-safe embed URL (bare HTML, no chrome) */
    public function embedUrl(): string
    {
        return url('/f/'.$this->slug.'/embed');
    }

    /**
     * BRD: CRM-LC-009 — QR code URL with UTM pre-filled for event/walk-in capture.
     * UTM params signal the QR code channel for analytics.
     */
    public function qrTargetUrl(): string
    {
        return $this->publicUrl()
            .'?utm_source=qr&utm_medium=event&utm_campaign='.urlencode($this->slug);
    }
}
