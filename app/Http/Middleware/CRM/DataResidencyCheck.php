<?php

declare(strict_types=1);

namespace App\Http\Middleware\CRM;

use App\Services\CRM\Compliance\DataResidencyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// BRD: CRM-CR-006 — Data residency enforcement: India-hosted servers
class DataResidencyCheck
{
    public function __construct(private readonly DataResidencyService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->service->isCompliant()) {
            abort(403, 'File uploads are restricted to India-hosted storage in this environment. Please contact your system administrator.');
        }

        return $next($request);
    }
}
