<?php

namespace App\Http\Requests\Bouquet;

use Illuminate\Foundation\Http\FormRequest;

class UploadBouquetImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'Размер изображения не должен превышать 2 МБ.',
        ];
    }
}
