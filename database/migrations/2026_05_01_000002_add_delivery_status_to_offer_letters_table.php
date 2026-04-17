<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('offer_letters', function (Blueprint $table) {
            $table->string('delivery_status', 32)->nullable()->after('sent_via');
            $table->string('delivery_message_id', 64)->nullable()->after('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::table('offer_letters', function (Blueprint $table) {
            $table->dropColumn(['delivery_status', 'delivery_message_id']);
        });
    }
};