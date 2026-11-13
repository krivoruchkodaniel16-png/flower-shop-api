<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Позиции заказа: готовый букет ИЛИ индивидуальный букет (ровно одно из двух —
 * проверяется в приложении). Цена за единицу фиксируется при оформлении.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Заказанные букеты удалить нельзя — их снимают с продажи (is_active = false)
            $table->foreignId('bouquet_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('custom_bouquet_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
