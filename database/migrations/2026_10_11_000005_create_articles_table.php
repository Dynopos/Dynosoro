<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The database is the source of truth; the website repo is rebuilt from it.
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->text('summary')->nullable();
            $table->longText('body_html')->nullable();
            $table->json('faq')->nullable();
            $table->text('cta_bridge')->nullable();
            $table->text('image_prompt')->nullable();
            // Path inside the website repo, e.g. blog/img/<slug>.webp
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            // writing | failed | ready | published | deleted
            $table->string('status', 20)->default('writing');
            // ai | customer
            $table->string('source', 20)->default('ai');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->json('check_notes')->nullable();
            $table->text('error')->nullable();
            $table->string('prompt_version', 40)->nullable();
            $table->string('commit_sha', 64)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('content_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
            $table->index(['site_id', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
