<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Индивидуальный букет, собранный покупателем в конструкторе.
 * Цена не хранится — вычисляется по составу и упаковке (3НФ).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_bouquets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Упаковку, использованную в букетах, удалить нельзя — её можно отключить (is_active)
            $table->foreignId('packaging_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('comment', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_bouquets');
    }
};
