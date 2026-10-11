<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A customer website on GitHub Pages that receives articles (spec §3.1).
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('domain');
            // owner/repo
            $table->string('github_repo');
            $table->string('branch', 100)->default('main');
            $table->text('niche');
            $table->text('audience')->nullable();
            $table->string('area')->nullable();
            $table->text('banned_topics')->nullable();
            $table->unsignedTinyInteger('frequency_days')->default(2);
            // active | paused | error | ai_paused
            $table->string('status', 20)->default('paused');
            $table->text('status_note')->nullable();
            $table->unsignedTinyInteger('failure_count')->default(0);
            $table->timestamp('next_publish_at')->nullable()->index();
            $table->timestamp('last_published_at')->nullable();
            $table->date('paid_until')->nullable();
            // Hook below every article (spec §3.2): title, text, button, whatsapp, message, url.
            $table->json('cta');
            // Optional AI bridge sentence from the topic to the hook (default off).
            $table->boolean('cta_bridge')->default(false);
            // Set when the one-off install (templates, sitemap index, robots) was committed.
            $table->timestamp('installed_at')->nullable();
            // Number of blog listing pages last written, so extra pages can be removed.
            $table->unsignedSmallInteger('blog_pages')->default(0);
            $table->timestamps();

            $table->unique(['workspace_id', 'github_repo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
