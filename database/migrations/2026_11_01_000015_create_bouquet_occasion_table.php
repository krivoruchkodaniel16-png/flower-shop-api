<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Поводы, для которых подходит букет — «многие ко многим» Bouquet ⇄ Occasion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bouquet_occasion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bouquet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('occasion_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['bouquet_id', 'occasion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bouquet_occasion');
    }
};
