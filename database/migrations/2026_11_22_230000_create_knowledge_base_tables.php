<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('knowledge_base_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug')->unique();
            $table->string('description', 500)->nullable();
            $table->string('color', 20)->default('#64748b');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('knowledge_base_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 60);
            $table->string('slug')->unique();
            $table->timestamps();
            $table->unique(['name']);
        });
        Schema::create('knowledge_base_articles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('knowledge_base_categories')->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('slug')->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('content');
            $table->string('cover_image_path')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });
        Schema::create('knowledge_base_article_tag', function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained('knowledge_base_articles')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('knowledge_base_tags')->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });
        Schema::create('knowledge_base_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained('knowledge_base_articles')->cascadeOnDelete();
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_attachments');
        Schema::dropIfExists('knowledge_base_article_tag');
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('knowledge_base_tags');
        Schema::dropIfExists('knowledge_base_categories');
    }
};
