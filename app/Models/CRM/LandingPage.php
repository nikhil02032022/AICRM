<?php

declare(strict_types=1);

namespace App\Models\CRM;

use App\Enums\CRM\LandingPageStatus;
use App\Models\User;
use App\Models\CRM\Scopes\InstitutionScope;
use App\Observers\CRM\AuditObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// BRD: CRM-LC-005 — Landing page entity for campaign-specific lead capture experiences
#[ObservedBy(AuditObserver::class)]
class LandingPage extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'landing_pages';

    /** @return list<string> */
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
        'web_form_id',
        'created_by',
        'name',
        'slug',
        'status',
        'theme_variant',
        'headline',
        'subheadline',
        'hero_image_url',
        'cta_label',
        'cta_secondary_label',
        'content',
        'attribution_params',
        'seo_title',
        'seo_description',
        'published_at',
    ];

    /** @return array<string, mixed> */
    protected function casts(): array
    {
        return [
            'status' => LandingPageStatus::class,
            'content' => 'array',
            'attribution_params' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function webForm(): BelongsTo
    {
        return $this->belongsTo(WebForm::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function landingPageViews(): HasMany
    {
        return $this->hasMany(LandingPageView::class);
    }

    public function publicUrl(): string
    {
        return url('/lp/'.$this->slug);
    }

    public function formEmbedUrl(): ?string
    {
        if ($this->webForm === null) {
            return null;
        }

        $query = http_build_query(array_filter($this->attribution_params ?? [], static fn (mixed $value): bool => $value !== null && $value !== ''));

        if ($query === '') {
            return $this->webForm->embedUrl();
        }

        return $this->webForm->embedUrl().'?'.$query;
    }
}