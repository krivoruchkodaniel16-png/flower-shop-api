<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Доставка заказа (один к одному с orders).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            // При удалении курьера доставка остаётся, но становится неназначенной
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('address');
            $table->string('recipient_name', 100);
            $table->string('recipient_phone', 20);
            $table->date('delivery_date')->index();
            $table->time('time_from');
            $table->time('time_to');                     // time_to > time_from (проверка в Form Request)
            $table->timestamp('delivered_at')->nullable();
            $table->string('courier_comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
