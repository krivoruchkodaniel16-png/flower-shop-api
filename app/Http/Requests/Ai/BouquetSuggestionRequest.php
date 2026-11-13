<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class BouquetSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:5', 'max:300'],
        ];
    }

    public function attributes(): array
    {
        return ['query' => 'запрос'];
    }
}
