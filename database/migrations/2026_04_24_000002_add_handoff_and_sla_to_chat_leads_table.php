<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-LC-006 — Live-agent handoff + SLA analytics metadata for chat enquiries.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_leads', function (Blueprint $table): void {
            $table->string('handoff_status', 30)->default('captured')->after('session_id');
            $table->unsignedBigInteger('assigned_to')->nullable()->after('lead_id');
            $table->timestamp('first_response_at')->nullable()->after('processed_at');
            $table->timestamp('last_message_at')->nullable()->after('first_response_at');
            $table->unsignedSmallInteger('inbound_messages')->default(1)->after('last_message_at');
            $table->unsignedSmallInteger('outbound_messages')->default(0)->after('inbound_messages');

            $table->index('handoff_status');
            $table->index('assigned_to');
            $table->index(['institution_id', 'handoff_status'], 'chat_leads_inst_handoff_idx');

            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_leads', function (Blueprint $table): void {
            $table->dropForeign(['assigned_to']);
            $table->dropIndex('chat_leads_inst_handoff_idx');
            $table->dropIndex(['handoff_status']);
            $table->dropIndex(['assigned_to']);

            $table->dropColumn([
                'handoff_status',
                'assigned_to',
                'first_response_at',
                'last_message_at',
                'inbound_messages',
                'outbound_messages',
            ]);
        });
    }
};