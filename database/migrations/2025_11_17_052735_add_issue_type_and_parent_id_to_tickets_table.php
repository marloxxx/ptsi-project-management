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
            $table->string('issue_type')->default('Task')->after('name');
            $table->foreignId('parent_id')->nullable()->after('epic_id')->constrained('tickets')->nullOnDelete();

            $table->index('issue_type');
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['issue_type']);
            $table->dropColumn(['issue_type', 'parent_id']);
        });
    }
};
