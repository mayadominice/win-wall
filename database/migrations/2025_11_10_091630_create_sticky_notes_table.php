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
        if (!Schema::hasTable('sticky_notes')) {
            Schema::create('sticky_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('title')->nullable();
                $table->text('text')->nullable();
                $table->string('color', 20);
                $table->string('icon', 20)->default('cupcake');
                $table->integer('x')->default(0);
                $table->integer('y')->default(0);
                $table->timestamps();
                
                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('sticky_note_boards')) {
            Schema::create('sticky_note_boards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('board_title')->default('Sticky Notes Board');
                $table->string('board_description')->nullable();
                $table->string('canvas_color', 20)->default('white');
                $table->timestamps();
                
                $table->unique('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sticky_notes');
        Schema::dropIfExists('sticky_note_boards');
    }
};
