<?php

namespace Database\Seeders;

use App\Models\Bouquet;
use App\Models\Flower;
use App\Models\Occasion;
use Illuminate\Database\Seeder;

class BouquetSeeder extends Seeder
{
    public function run(): void
    {
        $flower = fn (string $prefix) => Flower::where('name', 'like', $prefix.'%')->firstOrFail();
        $occasion = fn (string $slug) => Occasion::where('slug', $slug)->firstOrFail()->id;

        // [название, описание, [[цветок, кол-во]...], [поводы]]
        $bouquets = [
            ['Классика', '25 красных роз — беспроигрышный вариант для признания в любви.',
                [['Роза красная', 25]], ['love', 'anniversary', 'birthday']],
            ['Белое облако', 'Белые розы и эустома с эвкалиптом — нежный свадебный букет.',
                [['Роза белая', 9], ['Эустома белая', 5], ['Эвкалипт', 3]], ['wedding', 'thanks']],
            ['Пионовая нежность', 'Пышные розовые пионы — сезонный хит.',
                [['Пион розовый', 9]], ['birthday', 'mom', 'love']],
            ['Весеннее настроение', 'Микс тюльпанов к 8 Марта.',
                [['Тюльпан красный', 7], ['Тюльпан жёлтый', 7], ['Тюльпан белый', 7]], ['march-8', 'no-reason']],
            ['Солнечный день', 'Герберы и хризантемы — яркий и позитивный букет.',
                [['Гербера оранжевая', 5], ['Хризантема кустовая', 4], ['Эвкалипт', 2]], ['birthday', 'thanks', 'no-reason']],
            ['Пастельная акварель', 'Кустовые пионовидные розы, эустома и розовые розы в пастельных тонах.',
                [['Роза кустовая', 5], ['Эустома фиолетовая', 3], ['Роза розовая', 5]], ['mom', 'anniversary', 'birthday']],
            ['Синяя мечта', 'Гортензия и ирисы — необычное сочетание.',
                [['Гортензия голубая', 3], ['Ирис синий', 6]], ['thanks', 'no-reason']],
            ['Для мамы', 'Белые пионы и розовые розы — тёплый подарок самому близкому человеку.',
                [['Пион белый', 5], ['Роза розовая', 6]], ['mom', 'anniversary', 'march-8']],
            ['Комплимент', 'Небольшой букет из альстромерий — чтобы порадовать без повода.',
                [['Альстромерия', 7]], ['no-reason', 'thanks']],
            ['Юбилейный', '51 роза микс — для особого случая.',
                [['Роза красная', 17], ['Роза белая', 17], ['Роза розовая', 17]], ['anniversary', 'birthday']],
            ['Лилии', 'Ароматные белые лилии.',
                [['Лилия белая', 5], ['Эвкалипт', 2]], ['thanks', 'wedding']],
            ['Тюльпаны 15', 'Пятнадцать красных тюльпанов.',
                [['Тюльпан красный', 15]], ['march-8', 'love']],
        ];

        foreach ($bouquets as [$name, $description, $composition, $occasions]) {
            $cost = 0;
            $attach = [];
            foreach ($composition as [$prefix, $qty]) {
                $f = $flower($prefix);
                $cost += (float) $f->price * $qty;
                $attach[$f->id] = ['quantity' => $qty];
            }

            // Розничная цена: себестоимость цветов + упаковка/работа, округлённо до 50 ₽
            $price = (int) (ceil(($cost * 1.25 + 300) / 50) * 50);

            $bouquet = Bouquet::create([
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'is_active' => true,
            ]);

            $bouquet->flowers()->attach($attach);
            $bouquet->occasions()->attach(array_map($occasion, $occasions));
        }
    }
}
