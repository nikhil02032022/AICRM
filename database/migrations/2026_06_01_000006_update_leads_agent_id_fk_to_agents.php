<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AG-002 — Update leads.agent_id to FK agents table (previously unconstrained)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            // Drop any existing FK constraint to users table if present
            // (agent_id was added in Sprint 1 as a nullable column without a constraint)
            $table->foreignId('agent_id')
                ->nullable()
                ->change();

            $table->foreign('agent_id')
                ->references('id')
                ->on('agents')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropForeign(['agent_id']);
        });
    }
};
