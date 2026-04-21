<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-FM-004 — Shareable payment links via WhatsApp/SMS/Email
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_links', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('payment_transaction_id');

            $table->string('token', 80);
            $table->string('channel', 20);
            $table->string('recipient', 191)->nullable();

            $table->timestamp('shared_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('opened_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->unique('token');
            $table->index('institution_id');
            $table->index('payment_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};
