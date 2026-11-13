<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate:fresh --seed
     *
     * Порядок важен: справочники и пользователи создаются раньше,
     * чем записи, которые на них ссылаются.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            OccasionSeeder::class,
            FlowerSeeder::class,
            PackagingSeeder::class,
            BouquetSeeder::class,
            CustomBouquetSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
