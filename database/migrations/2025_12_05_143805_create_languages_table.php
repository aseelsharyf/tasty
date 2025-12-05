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
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // en, dv
            $table->string('name'); // English, Dhivehi
            $table->string('native_name'); // English, ދިވެހި
            $table->string('direction', 3)->default('ltr'); // ltr, rtl
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Add language_code to posts table
        Schema::table('posts', function (Blueprint $table) {
            $table->string('language_code', 10)->default('en')->after('author_id');
            $table->index('language_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['language_code']);
            $table->dropColumn('language_code');
        });

        Schema::dropIfExists('languages');
    }
};
