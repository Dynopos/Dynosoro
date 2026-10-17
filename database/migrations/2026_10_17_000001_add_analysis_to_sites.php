<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Business analysis of a website (field, services, topic clusters, topics to avoid).
 * Topics are only planned from an analysis the admin has confirmed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->json('analysis')->nullable()->after('banned_topics');
            $table->timestamp('analysis_confirmed_at')->nullable()->after('analysis');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['analysis', 'analysis_confirmed_at']);
        });
    }
};
