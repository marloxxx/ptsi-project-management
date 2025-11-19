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
        Schema::create('project_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('key')->index();
            $table->string('label');
            $table->string('type'); // 'text', 'number', 'select', 'date'
            $table->json('options')->nullable(); // For select fields: array of options
            $table->boolean('required')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'key']);
            $table->index(['project_id', 'active', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_custom_fields');
    }
};
