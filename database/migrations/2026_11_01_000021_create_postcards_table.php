<?php

use App\Enums\PostcardDesign;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Открытка к заказу (один к одному с orders, необязательна).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postcards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('design', 20)->default(PostcardDesign::Classic->value); // App\Enums\PostcardDesign
            $table->string('text', 500);
            $table->string('signature', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postcards');
    }
};
