<?php

namespace App\Http\Requests\Flower;

use App\Enums\FlowerColor;
use Illuminate\Validation\Rule;

class UpdateFlowerRequest extends StoreFlowerRequest
{
    public function rules(): array
    {
        return [
            // при изменении исключаем текущую запись из проверки уникальности
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('flowers', 'name')->ignore($this->route('flower'))],
            'color' => ['sometimes', 'required', Rule::enum(FlowerColor::class)],
            'price' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:1000000'],
            'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0', 'max:100000'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
