<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => array_values(array_filter([
                'nullable', 'date_format:Y-m-d',
                $this->filled('date_from') ? 'after_or_equal:date_from' : null,
            ])),
        ];
    }
}
