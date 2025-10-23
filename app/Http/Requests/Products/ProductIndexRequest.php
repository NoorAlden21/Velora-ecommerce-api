<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
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
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'q'           => ['nullable', 'string', 'max:200'],           // بحث نصي بسيط (اختياري)
            'filter'      => ['nullable', 'array'],                      // شكل: filter[attribute_id]=[attribute_value_id,...]
            'filter.*'    => ['array'],
            'filter.*.*'  => ['integer'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort'        => ['nullable', 'in:latest,price_asc,price_desc,name_asc,name_desc'],
        ];
    }

    public function filters(): array
    {
        // returns an array [attribute_id => [value_id,...]]
        return (array) $this->input('filter', []);
    }

    public function perPage(): int
    {
        return (int) ($this->input('per_page', 24));
    }

    public function sort(): ?string
    {
        return $this->input('sort');
    }
}
