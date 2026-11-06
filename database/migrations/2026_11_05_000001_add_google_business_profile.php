<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Google Business Profile posts. One Google account per workspace (the admin's) is
     * added as Manager on each customer's profile; each website picks its profile, and every
     * published article becomes one post there.
     */
    public function up(): void
    {
        Schema::create('google_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->text('refresh_token');
            $table->timestamp('connected_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->string('gbp_location', 190)->nullable()->after('target_keywords');
            $table->string('gbp_title')->nullable()->after('gbp_location');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('gbp_post')->nullable()->after('commit_sha');
            $table->timestamp('gbp_posted_at')->nullable()->after('gbp_post');
            $table->text('gbp_error')->nullable()->after('gbp_posted_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['gbp_post', 'gbp_posted_at', 'gbp_error']);
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['gbp_location', 'gbp_title']);
        });

        Schema::dropIfExists('google_connections');
    }
};
