<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Состав готового букета — связь «многие ко многим» Bouquet ⇄ Flower
 * с количеством стеблей.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bouquet_flower', function (Blueprint $table) {
            $table->id();
            // Удаление букета удаляет его состав
            $table->foreignId('bouquet_id')->constrained()->cascadeOnDelete();
            // Цветок, входящий в букет, удалить нельзя (409) — сначала нужно изменить состав
            $table->foreignId('flower_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();

            $table->unique(['bouquet_id', 'flower_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bouquet_flower');
    }
};
