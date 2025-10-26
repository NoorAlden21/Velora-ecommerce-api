<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q'             => ['nullable', 'string', 'max:120'],
            'category_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'category_slug' => ['nullable', 'string', 'exists:categories,slug'],
            'audience_id'   => ['nullable', 'integer', 'exists:audiences,id'],
            'price_min'     => ['nullable', 'integer', 'min:0'],
            'price_max'     => ['nullable', 'integer', 'min:0'],

            // Attributes: attrs[<attribute_id>][] = <attribute_value_id>
            'attrs'         => ['nullable', 'array'],
            'attrs.*'       => ['array'],
            'attrs.*.*'     => ['integer', 'exists:attribute_values,id'],

            // Options (by option_value_id): options[<option_id>][] = <option_value_id>
            'options'       => ['nullable', 'array'],
            'options.*'     => ['array'],
            'options.*.*'   => ['integer', 'exists:option_values,id'],

            'in_stock'      => ['nullable', 'boolean'],
            'out_of_stock'  => ['nullable', 'boolean'],
            'stock_min' => ['nullable', 'integer', 'min:0'],
            'stock_max' => ['nullable', 'integer', 'min:0'],

            'sort'          => ['nullable', 'string'], // price_asc, price_desc, name_asc, name_desc, latest
            'page'          => ['nullable', 'integer', 'min:1'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->input('per_page', 12));
    }

    public function sort(): string
    {
        return (string) ($this->input('sort', 'latest'));
    }

    // attribute filters [attribute_id => [value_ids...]]
    public function attrFilters(): array
    {
        return (array) $this->input('attrs', []);
    }

    // option filters (option_id => [value_ids...])
    public function optionFilters(): array
    {
        return (array) $this->input('options', []);
    }

    public function onlyFilters(): array
    {
        return $this->validated();
    }
}
