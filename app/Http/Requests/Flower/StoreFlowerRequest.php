<?php

namespace App\Http\Requests\Flower;

use App\Enums\FlowerColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFlowerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:flowers,name'],
            'color' => ['required', Rule::enum(FlowerColor::class)],
            'price' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Цветок с таким названием уже есть.',
            'price.gt' => 'Цена должна быть больше нуля.',
        ];
    }
}
