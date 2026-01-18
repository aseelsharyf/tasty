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
        Schema::table('product_stores', function (Blueprint $table) {
            $table->string('business_type')->nullable()->after('name');
            $table->string('contact_email')->nullable()->after('hotline');
            $table->string('location_label')->nullable()->after('address');
            $table->string('website_url')->nullable()->after('contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stores', function (Blueprint $table) {
            $table->dropColumn(['business_type', 'contact_email', 'location_label', 'website_url']);
        });
    }
};
