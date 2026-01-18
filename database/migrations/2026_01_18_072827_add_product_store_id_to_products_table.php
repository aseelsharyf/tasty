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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_store_id')
                ->nullable()
                ->after('product_category_id')
                ->constrained('product_stores')
                ->nullOnDelete();

            $table->dropColumn('affiliate_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['product_store_id']);
            $table->dropColumn('product_store_id');

            $table->string('affiliate_source')->nullable()->after('affiliate_url');
        });
    }
};
