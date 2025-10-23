<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class ResolveVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'selections' => ['nullable', 'array'],
            // we also accept top-level query params like ?color=black
        ];
    }

    public function normalizedSelections(): array
    {
        $sel = (array) $this->input('selections', []);
        foreach ($this->query() as $key => $val) {
            if (!array_key_exists($key, $sel) && is_string($val)) {
                $sel[$key] = $val;
            }
        }
        return $sel;
    }
}
