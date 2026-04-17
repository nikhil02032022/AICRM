<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-014 — Conditional offer management with document checklist
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_letters', function (Blueprint $table): void {
            $table->boolean('conditional')->default(false)->after('expires_at');
            $table->json('required_documents')->nullable()->after('conditional');
            $table->json('document_verification_status')->nullable()->after('required_documents');
        });
    }

    public function down(): void
    {
        Schema::table('offer_letters', function (Blueprint $table): void {
            $table->dropColumn(['conditional', 'required_documents', 'document_verification_status']);
        });
    }
};
