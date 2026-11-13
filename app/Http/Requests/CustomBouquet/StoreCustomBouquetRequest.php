<?php

namespace App\Http\Requests\CustomBouquet;

class StoreCustomBouquetRequest extends CalculateCustomBouquetRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => ['required', 'string', 'max:100'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'name' => 'название букета'];
    }
}
