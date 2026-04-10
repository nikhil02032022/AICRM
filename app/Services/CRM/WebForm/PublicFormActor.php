<?php

declare(strict_types=1);

namespace App\Services\CRM\WebForm;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * BRD: CRM-LC-001 — Lightweight pseudo-actor for public form submissions.
 *
 * When a student submits a public enquiry form, there is no authenticated user.
 * This value object satisfies the Authenticatable interface contract expected
 * by LeadService::create(), setting institution_id from the WebForm's context.
 * All role checks (e.g. hasRole('counsellor')) return false for this actor.
 */
final class PublicFormActor implements Authenticatable
{
    public readonly int $institution_id;

    public function __construct(int $institutionId)
    {
        $this->institution_id = $institutionId;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return 0; // system pseudo-ID
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken(mixed $value): void {}

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    /** Satisfies role checks in LeadService — public submissions are never counsellor-assigned here */
    public function hasRole(string $role): bool
    {
        return false;
    }

    /** Satisfy the id property access pattern used in LeadService logging */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'id' => 0,
            'institution_id' => $this->institution_id,
            default => null,
        };
    }
}
