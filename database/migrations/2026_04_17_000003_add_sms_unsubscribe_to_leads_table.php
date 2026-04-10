<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-CC-005 compatible — SMS DNC/opt-out fields on leads
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->timestamp('sms_unsubscribed_at')->nullable()->after('email_bounce_count');
            $table->timestamp('dnc_at')->nullable()->after('sms_unsubscribed_at');
            $table->string('dnc_reason')->nullable()->after('dnc_at');

            $table->index('sms_unsubscribed_at');
            $table->index('dnc_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex(['sms_unsubscribed_at']);
            $table->dropIndex(['dnc_at']);
            $table->dropColumn(['sms_unsubscribed_at', 'dnc_at', 'dnc_reason']);
        });
    }
};
