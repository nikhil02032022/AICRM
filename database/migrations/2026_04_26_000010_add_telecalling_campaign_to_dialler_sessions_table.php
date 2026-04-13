<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-TC-006 — Link dialler runs to telecalling campaigns for progress tracking
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dialler_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('telecalling_campaign_id')->nullable()->index()->after('campus_id');
        });
    }

    public function down(): void
    {
        Schema::table('dialler_sessions', function (Blueprint $table): void {
            $table->dropColumn('telecalling_campaign_id');
        });
    }
};
