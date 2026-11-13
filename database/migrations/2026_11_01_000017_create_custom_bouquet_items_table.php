<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Позиции индивидуального букета: какой цветок и сколько штук.
 * Реализует связь «многие ко многим» CustomBouquet ⇄ Flower.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_bouquet_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_bouquet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flower_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();

            $table->unique(['custom_bouquet_id', 'flower_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_bouquet_items');
    }
};
