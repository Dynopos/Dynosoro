<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Up to five keywords the admin wants each website to rank for; planned first. */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->json('target_keywords')->nullable()->after('banned_topics');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('target_keywords');
        });
    }
};
