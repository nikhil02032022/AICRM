<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-004 — Configurable application fee tracking at submission stage
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_form_drafts', function (Blueprint $table): void {
            $table->decimal('application_fee_amount', 10, 2)->nullable()->after('form_data');
            $table->string('application_fee_currency', 3)->default('INR')->after('application_fee_amount');
            $table->string('application_fee_status', 20)->default('not_required')->after('application_fee_currency');
            $table->string('application_fee_transaction_reference', 120)->nullable()->after('application_fee_status');
            $table->string('application_fee_gateway', 50)->nullable()->after('application_fee_transaction_reference');
            $table->timestamp('application_fee_paid_at')->nullable()->after('application_fee_gateway');

            $table->index(['institution_id', 'application_fee_status'], 'afd_inst_fee_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('application_form_drafts', function (Blueprint $table): void {
            $table->dropIndex('afd_inst_fee_status_idx');
            $table->dropColumn([
                'application_fee_amount',
                'application_fee_currency',
                'application_fee_status',
                'application_fee_transaction_reference',
                'application_fee_gateway',
                'application_fee_paid_at',
            ]);
        });
    }
};