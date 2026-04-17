<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-AP-012 — Customisable offer letter templates
#[ObservedBy(AuditObserver::class)]
class OfferLetterTemplate extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'offer_letter_templates';

    /**
     * @return list<string>
     */
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
        'name',
        'type',
        'description',
        'is_active',
        'html_template',
        'header_image_path',
        'footer_image_path',
        'signature_config',
        'available_merge_tags',
        'version',
        'last_used_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'available_merge_tags' => 'array',
            'signature_config' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Get default merge tags available for offer letter templates.
     *
     * @return list<string>
     */
    public static function getDefaultMergeTags(): array
    {
        return [
            'lead.first_name',
            'lead.full_name',
            'lead.email',
            'lead.mobile',
            'application.programme_name',
            'application.application_id',
            'application.applied_on',
            'offer.offer_id',
            'offer.generated_on',
            'offer.expires_on',
            'institution.name',
            'institution.address',
            'institution.contact_email',
            'institution.contact_phone',
            'institution.principal_name',
        ];
    }

    /**
     * Mark this template as used (for tracking popularity).
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
