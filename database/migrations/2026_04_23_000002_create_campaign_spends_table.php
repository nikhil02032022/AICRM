<?php

declare(strict_types=1);

use App\Enums\CRM\AttributionModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-017 — Campaign spend persistence for source/campaign cost-per-lead reporting.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_spends', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->string('source', 80);
            $table->string('campaign_name', 120)->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('INR');
            $table->string('attribution_model', 20)->default(AttributionModel::LAST_TOUCH->value);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('campus_id');
            $table->index('source');
            $table->index('campaign_name');
            $table->index(['period_start', 'period_end']);
            $table->index('attribution_model');

            $table->foreign('institution_id')->references('id')->on('institutions')->restrictOnDelete();
            $table->foreign('campus_id')->references('id')->on('campuses')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_spends');
    }
};
