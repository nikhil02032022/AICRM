<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-AR-021 — Bind API tokens to a specific institution for scoped BI access
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->unsignedBigInteger('institution_id')->nullable()->after('tokenable_id');
            $table->foreign('institution_id')->references('id')->on('institutions')->nullOnDelete();
            $table->index('institution_id');
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropForeign(['institution_id']);
            $table->dropIndex(['institution_id']);
            $table->dropColumn('institution_id');
        });
    }
};
