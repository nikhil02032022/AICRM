<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EC-018 — Video counselling meeting link and provider stored on session record
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('counselling_sessions', function (Blueprint $table): void {
            $table->string('meeting_link')->nullable()->after('mode');
            $table->enum('meeting_provider', ['google_meet', 'zoom', 'webrtc', 'none'])
                ->default('none')
                ->after('meeting_link');
        });
    }

    public function down(): void
    {
        Schema::table('counselling_sessions', function (Blueprint $table): void {
            $table->dropColumn(['meeting_link', 'meeting_provider']);
        });
    }
};
