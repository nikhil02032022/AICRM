<?php

declare(strict_types=1);

use App\Services\CRM\Payments\Support\PayloadRedactor;

it('redacts configured sensitive keys recursively', function () {
    config()->set('crm_payments.redact_keys', ['card_number', 'cvv', 'password']);

    $redacted = PayloadRedactor::redact([
        'amount' => 100,
        'card_number' => '4111111111111111',
        'meta' => [
            'cvv' => '123',
            'note' => 'ok',
            'nested' => ['password' => 'shh'],
        ],
    ]);

    expect($redacted['card_number'])->toBe('[REDACTED]')
        ->and($redacted['meta']['cvv'])->toBe('[REDACTED]')
        ->and($redacted['meta']['nested']['password'])->toBe('[REDACTED]')
        ->and($redacted['amount'])->toBe(100)
        ->and($redacted['meta']['note'])->toBe('ok');
});
