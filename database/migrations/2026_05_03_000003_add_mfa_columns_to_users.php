<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// NFR-SE-003 — MFA columns for TOTP-based multi-factor authentication.
// google2fa_secret and mfa_recovery_codes stored encrypted via model cast (AES-256-CBC).
// Additive migration; existing mfa_enabled (boolean) and mfa_verified_at remain untouched.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('google2fa_secret')->nullable()->after('mfa_enabled');
            $table->timestamp('mfa_enabled_at')->nullable()->after('google2fa_secret');
            $table->text('mfa_recovery_codes')->nullable()->after('mfa_enabled_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['google2fa_secret', 'mfa_enabled_at', 'mfa_recovery_codes']);
        });
    }
};
