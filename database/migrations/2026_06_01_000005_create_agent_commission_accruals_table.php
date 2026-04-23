<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AG-005 — Commission accrual auto-calculated on enrolment confirmation
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_commission_accruals', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('programme_id')->constrained('crm_programmes')->cascadeOnDelete();
            $table->foreignId('structure_id')
                ->nullable()
                ->constrained('agent_commission_structures')
                ->nullOnDelete();
            // Fee amount used as the accrual basis (for percentage calculations)
            $table->decimal('accrual_basis_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 10, 2);
            // pending | approved | paid | reversed
            $table->string('status')->default('pending');
            $table->timestamp('accrued_at');
            $table->timestamp('reversed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commission_accruals');
    }
};
