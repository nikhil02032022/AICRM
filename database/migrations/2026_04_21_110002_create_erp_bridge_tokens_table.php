<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-SP-007 — One-time signed token issued on enrolment for seamless ERP portal transition
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_bridge_tokens', function (Blueprint $table): void {
            $table->id();

            $table->string('lead_uuid', 36);
            $table->unsignedBigInteger('institution_id');
            $table->string('application_uuid', 36);

            $table->string('token_hash', 64);

            $table->timestamp('issued_at');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            $table->index('lead_uuid');
            $table->index(['token_hash'], 'erp_bridge_token_lookup');
            $table->index(['lead_uuid', 'application_uuid', 'used_at', 'expires_at'],
                'erp_bridge_active_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_bridge_tokens');
    }
};
