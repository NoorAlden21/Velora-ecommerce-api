<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
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
        return [
            'name'        => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:products,slug'],
            'sku'         => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'price_cents' => ['required', 'integer', 'min:0'],
            'currency'    => ['required', 'string', 'size:3'],
            'brand_id'    => ['nullable', 'integer', 'exists:brands,id'],
            'is_active'   => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'primary_category_id' => ['nullable', 'integer', 'exists:categories,id'],

            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'audience_ids' => ['array'],
            'audience_ids.*' => ['integer', 'exists:audiences,id'],

            'description'       => ['nullable', 'string'],
            'meta_title'        => ['nullable', 'string', 'max:255'],
            'meta_description'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
