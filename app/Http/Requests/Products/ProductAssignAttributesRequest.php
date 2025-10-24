<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ProductAssignAttributesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attribute_value_ids'   => ['required', 'array', 'min:1'],
            'attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
            'sync' => ['sometimes', 'boolean'], // default true replacement for provided attributes
        ];
    }

    public function sync(): bool
    {
        return (bool)($this->input('sync', true));
    }
}
