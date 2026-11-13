<?php

namespace App\Http\Requests\Flower;

use App\Enums\FlowerColor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FlowerIndexRequest extends FormRequest
{
    public const SORTABLE = ['name', 'price', 'stock_quantity', 'created_at'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', Rule::enum(FlowerColor::class)],
            'in_stock' => ['nullable', 'boolean'],
            'include_inactive' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(self::SORTABLE)],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
