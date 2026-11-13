<?php

namespace App\Http\Requests\Order;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'courier_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Courier->value),
            ],
        ];
    }

    public function messages(): array
    {
        return ['courier_id.exists' => 'Выбранный пользователь не является курьером.'];
    }
}
