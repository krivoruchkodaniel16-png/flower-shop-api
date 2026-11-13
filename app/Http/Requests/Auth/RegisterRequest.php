<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Email приводится к нижнему регистру до проверки. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9\s\-()]{10,20}$/'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'имя'];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Пользователь с таким email уже зарегистрирован.',
            'password.confirmed' => 'Пароль и подтверждение не совпадают.',
            'phone.regex' => 'Телефон указан в неверном формате.',
        ];
    }
}
