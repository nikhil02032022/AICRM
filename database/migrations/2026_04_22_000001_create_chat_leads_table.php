<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-006 — Live chat enquiry transcript + lead capture ledger
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_leads', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('session_id', 120);
            $table->string('visitor_name', 180)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->longText('transcript')->nullable();
            $table->json('attribution_params')->nullable();
            $table->boolean('consent_given')->default(false);
            $table->timestamp('consent_timestamp')->nullable();
            $table->string('consent_ip', 45)->nullable();
            $table->string('consent_form_version', 30)->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('lead_id');
            $table->index('session_id');
            $table->index('created_at');

            $table->foreign('institution_id')
                ->references('id')
                ->on('institutions')
                ->restrictOnDelete();

            $table->foreign('campus_id')
                ->references('id')
                ->on('campuses')
                ->nullOnDelete();

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_leads');
    }
};
