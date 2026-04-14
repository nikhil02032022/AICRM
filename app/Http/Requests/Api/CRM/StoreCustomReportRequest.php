<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\CRM;

use App\Enums\CRM\ReportEntity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

// BRD: CRM-AR-018 — Validate custom report definition creation
class StoreCustomReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:200'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'entity'             => ['required', new Enum(ReportEntity::class)],
            'selected_fields'    => ['required', 'array', 'min:1'],
            'selected_fields.*'  => ['required', 'string', 'max:100'],
            'filters'            => ['nullable', 'array'],
            'filters.*.field'    => ['required_with:filters', 'string', 'max:100'],
            'filters.*.operator' => ['required_with:filters', 'string', 'in:=,!=,<,>,<=,>=,like'],
            'filters.*.value'    => ['required_with:filters', 'string', 'max:255'],
            'group_by'           => ['nullable', 'string', 'max:100'],
            'sort_field'         => ['nullable', 'string', 'max:100'],
            'sort_direction'     => ['nullable', 'in:asc,desc'],
        ];
    }
}
