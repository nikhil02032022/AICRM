<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('institution_id')
                ->nullable()
                ->after('id')
                ->constrained('institutions')
                ->nullOnDelete();
            $table->foreignId('campus_id')
                ->nullable()
                ->after('institution_id')
                ->constrained('campuses')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->boolean('mfa_enabled')->default(false)->after('is_active');
            $table->timestamp('mfa_verified_at')->nullable()->after('mfa_enabled');

            $table->index(['institution_id', 'campus_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['institution_id']);
            $table->dropForeign(['campus_id']);
            $table->dropColumn(['institution_id', 'campus_id', 'is_active', 'mfa_enabled', 'mfa_verified_at']);
        });
    }
};
