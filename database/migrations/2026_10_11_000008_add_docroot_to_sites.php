<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            // Folder in the repo that the web server serves: '' for GitHub Pages,
            // 'public' for a Laravel app on Forge (e.g. dynolead.my).
            $table->string('docroot', 50)->default('')->after('branch');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('docroot');
        });
    }
};
