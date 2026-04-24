<?php

declare(strict_types=1);

namespace App\Http\Middleware\CRM;

use App\Services\CRM\Compliance\DltTemplateValidatorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

// BRD: CRM-CR-008 — SMS communications must use DLT-registered templates
class DltTemplateSmsCheck
{
    public function __construct(private readonly DltTemplateValidatorService $validator) {}

    public function handle(Request $request, Closure $next): Response
    {
        $body = $request->input('body') ?? $request->input('message') ?? '';

        if ($body && ! $this->validator->isRegistered($body)) {
            Log::warning('DLT check: SMS template not found in registered templates.', [
                'preview' => substr($body, 0, 50),
                'url'     => $request->url(),
            ]);
            // Advisory only — does not block, logs warning per TRAI v1 compliance
        }

        return $next($request);
    }
}
