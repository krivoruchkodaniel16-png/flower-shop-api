<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Заказы. Суммы фиксируются на момент оформления (цены могут меняться позже).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Пользователя с заказами удалить нельзя — история продаж сохраняется
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default(OrderStatus::New->value)->index(); // App\Enums\OrderStatus
            $table->decimal('items_total', 10, 2);
            $table->decimal('delivery_price', 10, 2)->default(0);
            $table->decimal('postcard_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->string('comment', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
