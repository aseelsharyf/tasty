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
        Schema::table('pages', function (Blueprint $table) {
            $table->string('language_code', 10)->default('en')->after('uuid');
            $table->foreign('language_code')
                ->references('code')
                ->on('languages')
                ->onDelete('restrict');
            $table->index('language_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['language_code']);
            $table->dropIndex(['language_code']);
            $table->dropColumn('language_code');
        });
    }
};
