<?php

declare(strict_types=1);

namespace App\Http\Middleware\CRM;

use App\Repositories\CRM\Import\IntegrationCredentialRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * VerifyWebhookSignature — validates incoming webhook payloads from external platforms.
 *
 * OWASP A01 / A07 — prevents spoofed webhook submissions to lead import endpoints.
 *
 * Strategy per channel (resolved from route parameter {integration}):
 *
 *  Google Ads  — X-Goog-Signature header: HMAC-SHA256(raw body, webhook_secret)
 *  Meta / FB   — X-Hub-Signature-256 header: sha256=HMAC-SHA256(raw body, app_secret)
 *  Portal      — X-Portal-Signature header: HMAC-SHA256(raw body, webhook_secret)
 *
 * All comparisons use hash_equals() to prevent timing attacks.
 *
 * Usage (route definition):
 *   ->middleware('crm.webhook:google')
 *   ->middleware('crm.webhook:meta')
 *   ->middleware('crm.webhook:portal')
 */
class VerifyWebhookSignature
{
    public function __construct(
        private readonly IntegrationCredentialRepositoryInterface $credentialRepository,
    ) {}

    public function handle(Request $request, Closure $next, string $channel = 'google'): SymfonyResponse
    {
        // Resolve credential by route UUID ({integration} parameter)
        $integrationUuid = $request->route('integration');

        if ($integrationUuid === null) {
            Log::warning('Webhook: missing integration UUID in route', ['channel' => $channel]);
            abort(403, 'Invalid webhook endpoint.');
        }

        $credential = $this->credentialRepository->findActiveByUuidWithoutScope((string) $integrationUuid);

        if ($credential === null) {
            Log::warning('Webhook: credential not found or inactive', [
                'uuid' => $integrationUuid,
                'channel' => $channel,
            ]);

            // Return 200 to prevent platform retry loops; just silently discard
            return response()->json(['status' => 'ignored'], 200);
        }

        $rawBody = $request->getContent();
        $valid = match ($channel) {
            'google' => $this->verifyGoogle($request, $rawBody, $credential->getCredential('webhook_secret') ?? ''),
            'meta' => $this->verifyMeta($request, $rawBody, $credential->getCredential('app_secret') ?? ''),
            'portal' => $this->verifyPortal($request, $rawBody, $credential->getCredential('webhook_secret') ?? ''),
            default => false,
        };

        if (!$valid) {
            Log::warning('Webhook: signature mismatch', [
                'uuid' => $integrationUuid,
                'channel' => $channel,
                'ip' => $request->ip(),
            ]);

            // 403 for signature mismatch — tells the platform to check their config
            abort(403, 'Invalid webhook signature.');
        }

        // Touch last_used_at without audit noise
        $credential->touchLastUsed();

        // Bind the resolved credential to the request for downstream use
        $request->attributes->set('webhook_credential', $credential);

        return $next($request);
    }

    /**
     * Google Lead Form Extensions signature verification.
     * Header: X-Goog-Signature contains HMAC-SHA256(raw_body, webhook_secret) hex-encoded.
     */
    private function verifyGoogle(Request $request, string $rawBody, string $secret): bool
    {
        if (empty($secret)) {
            return false;
        }

        $header = $request->header('X-Goog-Signature', '');

        if (empty($header)) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        // BRD: OWASP A07 — timing-safe comparison prevents timing attacks
        return hash_equals($expected, strtolower($header));
    }

    /**
     * Meta Lead Ads signature verification.
     * Header: X-Hub-Signature-256 = "sha256=<hex_hmac>"
     * Uses app_secret (Meta App Secret, not page token).
     */
    private function verifyMeta(Request $request, string $rawBody, string $secret): bool
    {
        if (empty($secret)) {
            return false;
        }

        $header = $request->header('X-Hub-Signature-256', '');

        if (empty($header) || !str_starts_with($header, 'sha256=')) {
            return false;
        }

        $received = substr($header, 7); // strip "sha256="
        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $received);
    }

    /**
     * Education portal signature verification (Shiksha, CollegeDekho, Careers360, Collegedunia).
     * Header: X-Portal-Signature contains HMAC-SHA256(raw_body, webhook_secret) hex-encoded.
     */
    private function verifyPortal(Request $request, string $rawBody, string $secret): bool
    {
        if (empty($secret)) {
            return false;
        }

        $header = $request->header('X-Portal-Signature', '');

        if (empty($header)) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, strtolower($header));
    }
}
