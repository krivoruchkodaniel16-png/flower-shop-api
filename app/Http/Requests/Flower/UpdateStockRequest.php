<?php

namespace App\Http\Requests\Flower;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Изменение остатка: положительное значение — поступление, отрицательное — списание (брак).
 */
class UpdateStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delta' => ['required', 'integer', 'not_in:0', 'between:-10000,10000'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['delta' => 'изменение остатка', 'reason' => 'причина'];
    }

    public function messages(): array
    {
        return ['delta.not_in' => 'Изменение остатка не может быть нулевым.'];
    }
}
