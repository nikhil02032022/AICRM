<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-SP-002 — Portal session tokens issued after successful OTP verification
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_sessions', function (Blueprint $table): void {
            $table->id();

            $table->string('lead_uuid', 36);
            $table->unsignedBigInteger('institution_id');

            $table->string('session_token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->string('device_fingerprint', 255)->nullable();

            $table->timestamps();

            $table->index('lead_uuid');
            $table->index(['session_token_hash', 'expires_at'], 'portal_session_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_sessions');
    }
};
