<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('target_keyword');
            // Lowercased, accent- and punctuation-free keyword: one topic per keyword per site.
            $table->string('keyword_key');
            // panduan | perbandingan | senarai | penerangan | musim
            $table->string('intent', 30)->nullable();
            $table->string('cluster')->nullable();
            $table->unsignedInteger('position')->default(0);
            // planned | used | skipped
            $table->string('status', 20)->default('planned');
            $table->string('source', 20)->default('ai');
            $table->timestamps();

            $table->unique(['site_id', 'keyword_key']);
            $table->index(['site_id', 'status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
