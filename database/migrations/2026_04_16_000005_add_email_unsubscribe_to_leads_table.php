<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-005 — DPDP Act compliant email unsubscribe tracking on leads
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->timestamp('email_unsubscribed_at')->nullable()->after('opt_out_at');
            $table->unsignedSmallInteger('email_bounce_count')->default(0)->after('email_unsubscribed_at');
            $table->index('email_unsubscribed_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex(['email_unsubscribed_at']);
            $table->dropColumn(['email_unsubscribed_at', 'email_bounce_count']);
        });
    }
};
