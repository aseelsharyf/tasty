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
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Which version this transition belongs to
            $table->foreignId('content_version_id')->constrained('content_versions')->cascadeOnDelete();

            // From and to status
            $table->string('from_status')->nullable(); // null for initial creation
            $table->string('to_status');

            // Who performed this transition
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();

            // Optional comment/reason for the transition
            $table->text('comment')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('content_version_id');
            $table->index('performed_by');
            $table->index('to_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
