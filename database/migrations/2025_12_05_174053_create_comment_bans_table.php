<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_bans', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // email, ip, user
            $table->string('value'); // The email, IP, or user_id
            $table->text('reason')->nullable();
            $table->foreignId('banned_by')->constrained('users');
            $table->timestamp('expires_at')->nullable(); // Null = permanent
            $table->timestamps();

            $table->unique(['type', 'value']);
            $table->index(['type', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_bans');
    }
};
