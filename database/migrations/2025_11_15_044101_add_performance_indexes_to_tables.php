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
        // Add index for project_notes note_date for filtering
        Schema::table('project_notes', function (Blueprint $table): void {
            $table->index('note_date');
        });

        // Add index for ticket_comments user_id for filtering by author
        Schema::table('ticket_comments', function (Blueprint $table): void {
            $table->index('user_id');
        });

        // Add index for ticket_histories user_id for filtering by actor
        Schema::table('ticket_histories', function (Blueprint $table): void {
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_notes', function (Blueprint $table): void {
            $table->dropIndex(['note_date']);
        });

        Schema::table('ticket_comments', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });

        Schema::table('ticket_histories', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });
    }
};
