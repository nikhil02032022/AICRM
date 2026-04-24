<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EC-019 — Walk-in queue token table; tokens are scoped to institution + campus and reset daily
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('walk_in_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();

            // Human-readable sequential number displayed on kiosk / display screen.
            // Uniqueness enforced daily per campus by WalkInQueueService::nextTokenNumber().
            $table->unsignedInteger('token_number');

            // Date the token was issued — used for daily counter reset query
            $table->date('token_date');

            // Optional lead stub — populated when visitor provides name + mobile on kiosk
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_mobile', 15)->nullable();
            $table->string('programme_interest')->nullable();

            $table->enum('status', ['waiting', 'called', 'serving', 'served', 'skipped'])
                ->default('waiting');

            // Set when counsellor calls the token
            $table->foreignId('counsellor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('skipped_at')->nullable();

            $table->timestamps();

            // Efficient lookup for today's active queue per campus
            $table->index(['institution_id', 'campus_id', 'status'], 'wit_inst_campus_status');
            // Efficient daily token number lookup
            $table->index(['campus_id', 'token_date'], 'wit_campus_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walk_in_tokens');
    }
};
