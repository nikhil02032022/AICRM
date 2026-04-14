<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-EC-005 — Custom fields per institution for leads and applications
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('campus_id')->nullable();
            $table->string('entity');           // lead|application — CustomFieldEntity enum
            $table->string('field_key', 100);   // snake_case machine key, unique per institution+entity
            $table->string('label', 150);       // Display label
            $table->string('type', 50);         // CustomFieldType enum
            $table->json('options')->nullable(); // For SELECT type: [{value, label}]
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible_in_list')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index(['institution_id', 'entity', 'is_active']);
            $table->unique(['institution_id', 'entity', 'field_key'], 'unique_field_key_per_institution_entity');
        });

        Schema::create('custom_field_values', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('custom_field_id');
            // Polymorphic: Lead | Application
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->index('custom_field_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('institution_id');
            $table->unique(['custom_field_id', 'entity_type', 'entity_id'], 'unique_cfv_per_entity');

            $table->foreign('custom_field_id')
                ->references('id')
                ->on('custom_fields')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};
