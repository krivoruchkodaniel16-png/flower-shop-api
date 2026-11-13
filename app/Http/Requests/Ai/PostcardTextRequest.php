<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostcardTextRequest extends FormRequest
{
    public const TONES = ['warm', 'formal', 'funny', 'romantic'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'occasion' => ['required', 'string', 'max:100'],
            'recipient' => ['required', 'string', 'min:3', 'max:300'],
            'tone' => ['nullable', Rule::in(self::TONES)],
        ];
    }

    public function attributes(): array
    {
        return ['occasion' => 'повод', 'recipient' => 'описание получателя', 'tone' => 'тон'];
    }
}
