<?php

namespace App\Http\Requests\Bouquet;

/**
 * Частичное изменение букета: передаются только изменяемые поля.
 */
class UpdateBouquetRequest extends StoreBouquetRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'price' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:10000000'],
            'is_active' => ['sometimes', 'boolean'],
            'flowers' => ['sometimes', 'required', 'array', 'min:1', 'max:15'],
            'flowers.*.flower_id' => ['required', 'integer', 'distinct', 'exists:flowers,id'],
            'flowers.*.quantity' => ['required', 'integer', 'min:1', 'max:201'],
            'occasion_ids' => ['sometimes', 'array'],
            'occasion_ids.*' => ['integer', 'distinct', 'exists:occasions,id'],
        ];
    }
}
