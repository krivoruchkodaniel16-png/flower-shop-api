<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CustomBouquet;
use App\Models\Flower;
use App\Models\Packaging;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomBouquetSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', UserRole::Customer)->get();
        $packagings = Packaging::all();
        $flowers = Flower::where('stock_quantity', '>', 30)->get();

        foreach ($customers as $index => $customer) {
            // У основного тестового покупателя — 3 букета, у остальных по одному
            $count = $customer->email === 'customer@flowers.test' ? 3 : 1;

            for ($i = 1; $i <= $count; $i++) {
                $custom = CustomBouquet::create([
                    'user_id' => $customer->id,
                    'packaging_id' => $packagings->random()->id,
                    'name' => 'Мой букет №'.$i,
                    'comment' => $i === 1 ? 'Без ленты, пожалуйста' : null,
                ]);

                // 2–3 вида цветов, общее количество стеблей — нечётное
                $picked = $flowers->random(fake()->numberBetween(2, 3))->values();
                $quantities = [];
                foreach ($picked as $k => $flower) {
                    $quantities[$flower->id] = fake()->randomElement([2, 3, 4, 5]);
                }
                if (array_sum($quantities) % 2 === 0) {
                    $quantities[$picked[0]->id]++;
                }

                foreach ($quantities as $flowerId => $qty) {
                    $custom->items()->create(['flower_id' => $flowerId, 'quantity' => $qty]);
                }
            }
        }
    }
}
