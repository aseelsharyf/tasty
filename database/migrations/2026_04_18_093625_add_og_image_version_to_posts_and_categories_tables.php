<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('og_image_version')->nullable()->after('featured_image_anchor');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('og_image_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('og_image_version');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('og_image_version');
        });
    }
};
