<?php

use App\Enums\PackagingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Упаковка для индивидуальных букетов.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packagings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 20)->default(PackagingType::Paper->value); // App\Enums\PackagingType
            $table->string('color', 50)->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedSmallInteger('max_flowers')->default(51); // вместимость
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packagings');
    }
};
