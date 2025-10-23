<?php

namespace App\Http\Requests\Categories;

use Illuminate\Foundation\Http\FormRequest;

class CategoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'], // filter by parent
            'active'    => ['nullable', 'boolean'],
            'q'         => ['nullable', 'string', 'max:200'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort'      => ['nullable', 'in:position_asc,position_desc,name_asc,name_desc,latest'],
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->input('per_page', 20));
    }

    public function sort(): string
    {
        return (string) ($this->input('sort', 'position_asc'));
    }
}
