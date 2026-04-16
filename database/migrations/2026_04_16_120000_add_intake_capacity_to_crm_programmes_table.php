<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_programmes', function (Blueprint $table): void {
            $table->unsignedInteger('intake_capacity')->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('crm_programmes', function (Blueprint $table): void {
            $table->dropColumn('intake_capacity');
        });
    }
};