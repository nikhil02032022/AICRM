<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// BRD: CRM-DM-004 — Reviewer comments on documents
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_document_comments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('application_document_id');
            $table->unsignedBigInteger('actor_id');

            $table->string('type', 30)->default('internal'); // DocumentCommentType
            $table->string('comment', 1000);

            $table->timestamps();
            $table->softDeletes();

            $table->index('institution_id');
            $table->index('application_document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_document_comments');
    }
};
