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
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('sprint_id')->nullable()->after('epic_id')->constrained()->nullOnDelete();
            $table->index(['sprint_id', 'ticket_status_id']);
            $table->index(['project_id', 'sprint_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['sprint_id']);
            $table->dropIndex(['sprint_id', 'ticket_status_id']);
            $table->dropIndex(['project_id', 'sprint_id']);
            $table->dropColumn('sprint_id');
        });
    }
};
