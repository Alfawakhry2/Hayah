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
        Schema::create('child_abilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->enum('can_sit', ['yes', 'no', 'with_help'])->default('no');
            $table->enum('can_walk',  ['yes', 'no', 'with_help'])->default('no');
            $table->enum('uses_hands',  ['yes', 'no', 'one_hand'])->default('no');
            $table->json('target_goals')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_abilities');
    }
};
