<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product')->id ?? null;

        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'slug'        => ['sometimes', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku'         => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId, 'id')],
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'currency'    => ['sometimes', 'string', 'size:3'],
            'is_active'   => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'primary_category_id' => ['nullable', 'integer', 'exists:categories,id'],

            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'audience_ids' => ['sometimes', 'array'],
            'audience_ids.*' => ['integer', 'exists:audiences,id'],

            'description'       => ['nullable', 'string'],
            'meta_title'        => ['nullable', 'string', 'max:255'],
            'meta_description'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
