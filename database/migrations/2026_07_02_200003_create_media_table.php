<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('media_category_id')->constrained('media_categories')->restrictOnDelete();
            $table->string('company_name');
            $table->string('brand_name');
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('director')->nullable();
            $table->string('chief_editor')->nullable();
            $table->text('description')->nullable();
            $table->string('verification_status')->default('draft'); // draft, pending, approved, revision, rejected
            $table->unsignedInteger('verification_score')->default(0);
            $table->unsignedInteger('completeness_percentage')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id']);
            $table->index(['media_category_id']);
            $table->index(['verification_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
