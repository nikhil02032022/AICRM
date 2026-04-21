<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Resources\Json\JsonResource;

// BRD: CRM-AP-019 — Conversion rate report resource (applications → enrolled by programme/batch/source/counsellor)
class ConversionRateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'programme_id'       => $this['programme_id'],
            'programme_name'     => $this['programme_name'],
            'batch'              => $this['batch'],
            'source'             => $this['source'],
            'counsellor_id'      => $this['counsellor_id'],
            'counsellor_name'    => $this['counsellor_name'],
            'total_applications' => $this['total_applications'],
            'enrolled_count'     => $this['enrolled_count'],
            'conversion_rate'    => $this['conversion_rate'],
        ];
    }
}
