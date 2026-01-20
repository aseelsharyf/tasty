<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recipe_submissions', function (Blueprint $table) {
            $table->string('submitter_avatar')->nullable()->after('submitter_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipe_submissions', function (Blueprint $table) {
            $table->dropColumn('submitter_avatar');
        });
    }
};
