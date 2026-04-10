<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-004 — Custom sender domain with SPF/DKIM/DMARC support
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sender_domains', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('domain'); // e.g. "admissions.gim.ac.in"
            $table->string('default_from_name');
            $table->string('default_from_email');
            $table->boolean('spf_verified')->default(false);
            $table->boolean('dkim_verified')->default(false);
            $table->boolean('dmarc_verified')->default(false);
            $table->string('provider', 20); // EmailProvider enum: MAILGUN, POSTMARK, SES, SENDGRID
            $table->foreignId('credentials_id')->nullable()->constrained('integration_credentials')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('domain');
            $table->index(['institution_id', 'is_default']);
            $table->unique(['institution_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sender_domains');
    }
};
