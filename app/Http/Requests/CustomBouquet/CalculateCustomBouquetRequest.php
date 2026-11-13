<?php

namespace App\Http\Requests\CustomBouquet;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Состав букета для конструктора (расчёт цены без сохранения).
 */
class CalculateCustomBouquetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'packaging_id' => ['required', 'integer', 'exists:packagings,id'],
            'items' => ['required', 'array', 'min:1', 'max:15'],
            'items.*.flower_id' => ['required', 'integer', 'distinct', 'exists:flowers,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:101'],
        ];
    }

    public function attributes(): array
    {
        return [
            'items' => 'состав букета',
            'items.*.flower_id' => 'цветок',
            'items.*.quantity' => 'количество',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Добавьте в букет хотя бы один цветок.',
            'items.*.flower_id.distinct' => 'Один и тот же цветок указан несколько раз.',
        ];
    }
}
