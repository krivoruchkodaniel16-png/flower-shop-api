<?php

namespace App\Http\Requests\Order;

use App\Enums\PostcardDesign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostcardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:500'],
            'design' => ['nullable', Rule::enum(PostcardDesign::class)],
            'signature' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['text' => 'текст открытки', 'design' => 'оформление', 'signature' => 'подпись'];
    }
}
