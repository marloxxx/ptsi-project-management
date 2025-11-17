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
        Schema::create('saved_filters', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner'); // owner_type, owner_id (polymorphic) - already creates index
            $table->string('name');
            $table->json('query'); // Filter criteria as JSON
            $table->string('visibility')->default('private'); // 'private', 'team', 'project', 'public'
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();

            // Indexes for performance (morphs already creates index for owner_type and owner_id)
            $table->index('project_id');
            $table->index('visibility');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_filters');
    }
};
