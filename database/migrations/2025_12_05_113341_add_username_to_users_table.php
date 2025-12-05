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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('uuid');
        });

        // Generate usernames for existing users from their names
        DB::table('users')->whereNull('username')->cursor()->each(function ($user) {
            $baseSlug = Str::slug($user->name);
            $slug = $baseSlug ?: 'user';
            $counter = 1;

            while (DB::table('users')->where('username', $slug)->where('id', '!=', $user->id)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $slug]);
        });

        // Make username required after populating
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
