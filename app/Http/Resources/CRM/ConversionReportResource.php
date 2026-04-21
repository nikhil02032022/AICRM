<?php

declare(strict_types=1);

namespace App\Http\Resources\CRM;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversionReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'programme_id' => $this['programme_id'],
            'programme_name' => $this['programme_name'],
            'source' => $this['source'],
            'counsellor_id' => $this['counsellor_id'],
            'counsellor_name' => $this['counsellor_name'],
            'conversions' => $this['conversions'],
            'from_date' => $this['from_date'],
            'to_date' => $this['to_date'],
        ];
    }
}
