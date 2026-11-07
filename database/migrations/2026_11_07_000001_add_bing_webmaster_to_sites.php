<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bing Webmaster Tools: each website is added to the admin's Bing account, verified with
     * BingSiteAuth.xml, and its sitemap submitted once.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->timestamp('bing_verified_at')->nullable()->after('gbp_title');
            $table->timestamp('bing_sitemap_at')->nullable()->after('bing_verified_at');
            $table->text('bing_error')->nullable()->after('bing_sitemap_at');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['bing_verified_at', 'bing_sitemap_at', 'bing_error']);
        });
    }
};
