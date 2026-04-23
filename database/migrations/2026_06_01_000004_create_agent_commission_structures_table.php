<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AG-004 — Commission structure configuration per agent and programme
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_commission_structures', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('programme_id')->constrained('crm_programmes')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            // per_enrolment | per_application | percentage_fee
            $table->string('structure_type');
            // Fixed amount (PerEnrolment / PerApplication)
            $table->decimal('amount', 10, 2)->nullable();
            // Percentage (PercentageFee)
            $table->decimal('percentage', 5, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commission_structures');
    }
};
