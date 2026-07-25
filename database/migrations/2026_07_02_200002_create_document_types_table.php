<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->json('allowed_extensions')->nullable();
            $table->unsignedInteger('max_file_size_mb')->nullable();
            $table->string('icon', 100)->nullable();
            $table->unsignedInteger('weight')->default(1);
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('validity_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
