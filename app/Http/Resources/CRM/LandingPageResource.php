<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use App\Models\CRM\LandingPage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property LandingPage $resource
 */
class LandingPageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->resource->uuid,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->label(),
            'theme_variant' => $this->resource->theme_variant,
            'headline' => $this->resource->headline,
            'subheadline' => $this->resource->subheadline,
            'hero_image_url' => $this->resource->hero_image_url,
            'cta_label' => $this->resource->cta_label,
            'cta_secondary_label' => $this->resource->cta_secondary_label,
            'content' => $this->resource->content,
            'attribution_params' => $this->resource->attribution_params,
            'seo_title' => $this->resource->seo_title,
            'seo_description' => $this->resource->seo_description,
            'view_count' => isset($this->resource->landing_page_views_count)
                ? (int) $this->resource->landing_page_views_count
                : (int) $this->resource->landingPageViews()->count(),
            'view_count_last_7d' => isset($this->resource->view_count_last_7d)
                ? (int) $this->resource->view_count_last_7d
                : (int) $this->resource->landingPageViews()->where('viewed_at', '>=', now()->subDays(7))->count(),
            'public_url' => $this->resource->publicUrl(),
            'form_embed_url' => $this->resource->formEmbedUrl(),
            'web_form' => $this->resource->webForm !== null ? [
                'uuid' => $this->resource->webForm->uuid,
                'name' => $this->resource->webForm->name,
            ] : null,
            'published_at' => $this->resource->published_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}