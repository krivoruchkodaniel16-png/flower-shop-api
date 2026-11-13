<?php

namespace App\Http\Requests\Packaging;

use App\Enums\PackagingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePackagingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sometimes = $this->isMethod('post') ? [] : ['sometimes'];

        return [
            'name' => [...$sometimes, 'required', 'string', 'max:100'],
            'type' => [...$sometimes, 'required', Rule::enum(PackagingType::class)],
            'color' => ['sometimes', 'nullable', 'string', 'max:50'],
            'price' => [...$sometimes, 'required', 'numeric', 'min:0', 'max:100000'],
            'max_flowers' => [...$sometimes, 'required', 'integer', 'min:3', 'max:201'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
