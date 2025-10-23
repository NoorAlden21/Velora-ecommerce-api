<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ProductGenerateVariantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'remove_missing'     => ['sometimes', 'boolean'],
            'default_stock'      => ['sometimes', 'integer', 'min:0'],
            'default_active'     => ['sometimes', 'boolean'],
            'default_currency'   => ['sometimes', 'string', 'size:3'],
            'default_price_cents' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
