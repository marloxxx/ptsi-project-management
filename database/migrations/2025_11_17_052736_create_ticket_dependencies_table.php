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
        Schema::create('ticket_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('depends_on_ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('type')->default('blocks'); // 'blocks' or 'relates'
            $table->timestamps();

            $table->unique(['ticket_id', 'depends_on_ticket_id', 'type']);
            $table->index('ticket_id');
            $table->index('depends_on_ticket_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_dependencies');
    }
};
