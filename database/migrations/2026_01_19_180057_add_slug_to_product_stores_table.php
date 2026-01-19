<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_stores', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
        });

        // Generate slugs for existing stores
        $stores = DB::table('product_stores')->whereNull('deleted_at')->get();
        foreach ($stores as $store) {
            $slug = Str::slug($store->name);
            $counter = 1;
            $originalSlug = $slug;

            while (DB::table('product_stores')->where('slug', $slug)->where('id', '!=', $store->id)->exists()) {
                $slug = "{$originalSlug}-{$counter}";
                $counter++;
            }

            DB::table('product_stores')->where('id', $store->id)->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stores', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
