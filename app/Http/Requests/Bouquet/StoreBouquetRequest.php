<?php

namespace App\Http\Requests\Bouquet;

use Illuminate\Foundation\Http\FormRequest;

class StoreBouquetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'is_active' => ['sometimes', 'boolean'],
            // состав букета (многие ко многим с количеством)
            'flowers' => ['required', 'array', 'min:1', 'max:15'],
            'flowers.*.flower_id' => ['required', 'integer', 'distinct', 'exists:flowers,id'],
            'flowers.*.quantity' => ['required', 'integer', 'min:1', 'max:201'],
            // поводы (многие ко многим)
            'occasion_ids' => ['sometimes', 'array'],
            'occasion_ids.*' => ['integer', 'distinct', 'exists:occasions,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'flowers.*.flower_id' => 'цветок',
            'flowers.*.quantity' => 'количество цветов',
            'occasion_ids.*' => 'повод',
        ];
    }

    public function messages(): array
    {
        return [
            'price.gt' => 'Цена должна быть больше нуля.',
            'flowers.required' => 'Укажите состав букета.',
        ];
    }
}
