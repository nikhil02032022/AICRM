<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TC-002 — Step definitions and branch rules per call script
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_script_steps', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('campus_id')->nullable()->index();
            $table->unsignedBigInteger('call_script_id')->index();
            $table->string('step_key', 80);
            $table->unsignedInteger('step_order')->default(1);
            $table->text('prompt_text');
            $table->string('response_type', 20)->default('text');
            $table->json('options')->nullable();
            $table->json('branch_rules')->nullable();
            $table->string('default_next_step_key', 80)->nullable();
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['call_script_id', 'step_order']);
            $table->unique(['call_script_id', 'step_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_script_steps');
    }
};
