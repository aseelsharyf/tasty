<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('type')->default('user')->index()->after('email');
        });

        // Set existing users with CMS roles to 'staff'
        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('model_id')
                    ->from('model_has_roles')
                    ->where('model_type', 'App\\Models\\User')
                    ->whereIn('role_id', function ($q) {
                        $q->select('id')
                            ->from('roles')
                            ->whereIn('name', ['Admin', 'Developer', 'Editor', 'Writer', 'Photographer']);
                    });
            })
            ->update(['type' => 'staff']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
