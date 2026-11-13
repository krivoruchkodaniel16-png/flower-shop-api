<?php

namespace App\Http\Requests\Bouquet;

use App\Enums\FlowerColor;
use App\Models\Bouquet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Параметры каталога: GET /api/bouquets?occasion_id=1&flower_ids[]=3&price_max=5000&search=роз&sort=price&order=asc&per_page=12
 */
class BouquetIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'occasion_id' => ['nullable', 'integer', 'exists:occasions,id'],
            'flower_ids' => ['nullable', 'array', 'max:5'],
            'flower_ids.*' => ['integer', 'distinct', 'exists:flowers,id'],
            'color' => ['nullable', Rule::enum(FlowerColor::class)],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => array_values(array_filter([
                'nullable', 'numeric', 'min:0',
                $this->filled('price_min') ? 'gte:price_min' : null,
            ])),
            'include_inactive' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(Bouquet::SORTABLE)],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_max.gte' => 'Максимальная цена должна быть не меньше минимальной.',
            'sort.in' => 'Сортировка возможна только по полям: '.implode(', ', Bouquet::SORTABLE).'.',
        ];
    }
}
