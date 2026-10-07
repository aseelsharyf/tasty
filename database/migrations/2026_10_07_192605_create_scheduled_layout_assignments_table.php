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
        Schema::create('scheduled_layout_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('content_version_id')->constrained()->cascadeOnDelete();
            $table->string('layout_type');
            $table->foreignId('page_layout_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section_id');
            $table->unsignedSmallInteger('slot_index');
            $table->timestamp('scheduled_at');
            $table->string('status')->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_layout_assignments');
    }
};
