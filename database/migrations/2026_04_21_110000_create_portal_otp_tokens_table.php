<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-SP-002 — Portal OTP tokens for email-based applicant authentication
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_otp_tokens', function (Blueprint $table): void {
            $table->id();

            $table->string('lead_uuid', 36);
            $table->unsignedBigInteger('institution_id');

            // channel kept for future SMS support; only 'email' used in Sprint 4
            $table->string('channel', 10)->default('email');
            $table->string('token_hash', 64);

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->index('lead_uuid');
            $table->index(['lead_uuid', 'institution_id', 'used_at', 'expires_at'],
                'portal_otp_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_otp_tokens');
    }
};
