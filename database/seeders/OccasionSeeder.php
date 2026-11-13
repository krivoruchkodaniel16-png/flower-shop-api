<?php

namespace Database\Seeders;

use App\Models\Occasion;
use Illuminate\Database\Seeder;

class OccasionSeeder extends Seeder
{
    public function run(): void
    {
        $occasions = [
            'birthday' => 'День рождения',
            'anniversary' => 'Юбилей',
            'march-8' => '8 Марта',
            'wedding' => 'Свадьба',
            'love' => 'Признание в любви',
            'mom' => 'Для мамы',
            'thanks' => 'Благодарность',
            'no-reason' => 'Без повода',
        ];

        foreach ($occasions as $slug => $name) {
            Occasion::create(['slug' => $slug, 'name' => $name]);
        }
    }
}
