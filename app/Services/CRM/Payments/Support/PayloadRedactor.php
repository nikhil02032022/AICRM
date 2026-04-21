<?php

declare(strict_types=1);

namespace App\Services\CRM\Payments\Support;

// BRD: DPDP — strip sensitive payment fields before persisting raw payloads.
final class PayloadRedactor
{
    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public static function redact(array $payload): array
    {
        $keys = array_map('strtolower', (array) config('crm_payments.redact_keys', []));

        $walk = static function (&$value) use (&$walk, $keys): void {
            if (! is_array($value)) {
                return;
            }
            foreach ($value as $k => &$v) {
                if (is_string($k) && in_array(strtolower($k), $keys, true)) {
                    $v = '[REDACTED]';
                    continue;
                }
                if (is_array($v)) {
                    $walk($v);
                }
            }
        };

        $walk($payload);

        return $payload;
    }
}
