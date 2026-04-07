<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log table — A09 OWASP compliance.
 *
 * Captures ALL CRM data mutations: who changed what, when, on which entity.
 * DPDP: old_values and new_values are PII-scrubbed before write (AuditObserver).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100);  // e.g. App\Models\CRM\Lead
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 30);        // created|updated|deleted|restored
            $table->json('old_values')->nullable();  // PII-scrubbed
            $table->json('new_values')->nullable();  // PII-scrubbed
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('institution_id');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['institution_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
