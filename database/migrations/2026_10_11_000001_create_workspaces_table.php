<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One workspace per customer (business). Fasa 0: Bob's internal workspace holds all websites.
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('phone', 30)->nullable();
            // dalaman (Bob) | pelanggan
            $table->string('plan', 30)->default('pelanggan');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role', 20)->default('owner')->after('workspace_id');
            $table->boolean('is_admin')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('workspace_id');
            $table->dropColumn(['role', 'is_admin']);
        });

        Schema::dropIfExists('workspaces');
    }
};
