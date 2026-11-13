<?php

namespace App\Http\Requests\Occasion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOccasionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sometimes = $this->isMethod('post') ? [] : ['sometimes'];

        return [
            'name' => [...$sometimes, 'required', 'string', 'max:100'],
            'slug' => [
                ...$sometimes, 'required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('occasions', 'slug')->ignore($this->route('occasion')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Код может содержать только латинские буквы, цифры и дефис.',
            'slug.unique' => 'Повод с таким кодом уже существует.',
        ];
    }
}
