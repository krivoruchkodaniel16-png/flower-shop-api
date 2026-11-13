<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => array_values(array_filter([
                'nullable', 'date_format:Y-m-d',
                $this->filled('date_from') ? 'after_or_equal:date_from' : null,
            ])),
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(Order::SORTABLE)],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
