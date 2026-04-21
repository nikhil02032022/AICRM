<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AP-017 — Programme association on application record for conversion reporting
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->unsignedBigInteger('programme_id')->nullable()->after('lead_uuid');
            $table->index('programme_id');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropIndex(['programme_id']);
            $table->dropColumn('programme_id');
        });
    }
};
