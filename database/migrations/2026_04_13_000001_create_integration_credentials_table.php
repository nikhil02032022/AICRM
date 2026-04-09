<?php

declare(strict_types=1);

use App\Enums\CRM\IntegrationChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-SA-010 — Integration credential management with encrypted storage
// Stores per-channel API keys / webhook secrets — credentials column is AES-256 encrypted at app layer
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_credentials', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy — BRD NFR-MT-001
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();

            // Channel identifier — which platform this credential is for
            $table->string('channel')->default(IntegrationChannel::GOOGLE_ADS->value);

            // Human-readable internal label, e.g. "MBA 2026 Google Campaign"
            $table->string('label', 200);

            // AES-256 encrypted JSON: { webhook_secret, access_token, page_id, form_id, verify_token, ... }
            // BRD: CRM-SA-010 — Encrypted storage; never logged or serialised in API responses
            $table->text('credentials');

            $table->boolean('is_active')->default(true);

            // Diagnostic — updated on every successful webhook hit
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('institution_id');
            $table->index(['institution_id', 'channel', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_credentials');
    }
};
