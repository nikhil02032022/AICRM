<?php

declare(strict_types=1);

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

// BRD: CRM-DM-007 — Validation for OTP verification step of Aadhaar eKYC
final class VerifyAadhaarOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'digits:6'],
        ];
    }
}
