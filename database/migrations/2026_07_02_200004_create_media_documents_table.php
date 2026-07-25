<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('document_types')->restrictOnDelete();
            $table->string('document_number');
            $table->date('issue_date');
            $table->date('expiration_date')->nullable();
            $table->string('verification_status')->default('pending'); // pending, approved, rejected, revision
            $table->text('verification_notes')->nullable();
            $table->foreignId('verifier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['media_id']);
            $table->index(['document_type_id']);
            $table->index(['verification_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_documents');
    }
};
