<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ProductColorImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.option_value_id' => ['required', 'integer', 'exists:option_values,id'],
            'items.*.files' => ['required', 'array', 'min:1'],
            'items.*.files.*.url' => ['required', 'string', 'max:2048'],
            'items.*.files.*.position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
