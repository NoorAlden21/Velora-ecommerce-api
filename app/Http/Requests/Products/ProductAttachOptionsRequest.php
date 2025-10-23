<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ProductAttachOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'options' => ['required', 'array', 'min:1'],
            'options.*.option_id' => ['required', 'integer', 'exists:options,id'],
            'options.*.value_ids' => ['required', 'array', 'min:1'],
            'options.*.value_ids.*' => ['integer', 'exists:option_values,id'],
        ];
    }
}
