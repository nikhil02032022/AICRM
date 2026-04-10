<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EC-004 — Complete activity timeline (calls, emails, WhatsApp, notes, status changes etc.)
// displayed chronologically on the lead record
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            // Multi-tenancy — every activity row is institution-scoped
            $table->unsignedBigInteger('institution_id');
            $table->foreign('institution_id')->references('id')->on('institutions');

            // Polymorphic subject — Lead, Application, etc.
            $table->morphs('subject'); // subject_type + subject_id with index

            // Activity classification
            $table->string('type', 40); // ActivityType enum value
            $table->string('direction', 20)->nullable(); // outbound / inbound / internal
            $table->string('channel', 40)->nullable();   // email / sms / whatsapp / phone / web
            $table->text('body')->nullable();             // human-readable description or note content

            // Who performed the action (nullable = system-generated)
            $table->unsignedBigInteger('performed_by_id')->nullable();
            $table->foreign('performed_by_id')->references('id')->on('users')->nullOnDelete();

            // Arbitrary structured data (e.g. {from: 'contacted', to: 'counselling_scheduled'})
            // DPDP: must never contain raw PII (mobile, email, Aadhaar, etc.)
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Query performance indexes
            $table->index('institution_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
