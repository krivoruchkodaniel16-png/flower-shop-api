<?php

namespace App\Http\Requests\CustomBouquet;

class UpdateCustomBouquetRequest extends StoreCustomBouquetRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:500'],
            'packaging_id' => ['sometimes', 'required', 'integer', 'exists:packagings,id'],
            'items' => ['sometimes', 'required', 'array', 'min:1', 'max:15'],
            'items.*.flower_id' => ['required', 'integer', 'distinct', 'exists:flowers,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:101'],
        ];
    }
}
