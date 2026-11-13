<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Цветы (поштучно) — склад и материал для конструктора.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flowers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->index();
            $table->string('color', 20)->index();          // App\Enums\FlowerColor
            $table->decimal('price', 10, 2);               // цена за 1 шт., > 0 (проверка в Form Request)
            $table->unsignedInteger('stock_quantity')->default(0); // остаток на складе
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flowers');
    }
};
