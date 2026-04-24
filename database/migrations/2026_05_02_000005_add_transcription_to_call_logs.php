<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AI-007 — Add transcription columns to call_logs for AI-powered post-call summary
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_logs', function (Blueprint $table): void {
            $table->text('transcript_text')->nullable()->after('recording_url');
            $table->json('transcription_summary')->nullable()->after('transcript_text');
            $table->enum('transcription_status', ['pending', 'processing', 'completed', 'failed'])
                ->nullable()
                ->default(null)
                ->after('transcription_summary');
            $table->string('transcription_model')->nullable()->after('transcription_status');
            $table->unsignedInteger('transcription_token_count')->nullable()->after('transcription_model');
            $table->timestamp('transcribed_at')->nullable()->after('transcription_token_count');
        });
    }

    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table): void {
            $table->dropColumn([
                'transcript_text',
                'transcription_summary',
                'transcription_status',
                'transcription_model',
                'transcription_token_count',
                'transcribed_at',
            ]);
        });
    }
};
