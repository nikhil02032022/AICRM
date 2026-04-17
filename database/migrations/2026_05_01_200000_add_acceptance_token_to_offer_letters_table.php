<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-015 — Public student portal acceptance token
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_letters', function (Blueprint $table): void {
            $table->string('acceptance_token', 64)->nullable()->unique()->after('document_verification_status');
            $table->timestamp('acceptance_token_expires_at')->nullable()->after('acceptance_token');
        });
    }

    public function down(): void
    {
        Schema::table('offer_letters', function (Blueprint $table): void {
            $table->dropColumn(['acceptance_token', 'acceptance_token_expires_at']);
        });
    }
};
