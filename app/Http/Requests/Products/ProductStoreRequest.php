<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('data')) {
            $decoded = json_decode($this->input('data'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                if (isset($decoded['color_images']) && is_array($decoded['color_images'])) {
                    foreach ($decoded['color_images'] as &$row) {
                        unset($row['file']); // لا تسمح للـ JSON يكتب فوق UploadedFile
                    }
                }
                $this->merge($decoded);
            }
        }
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
            'currency'    => ['nullable', 'string', 'size:3'],
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

            'status' => ['nullable', Rule::in(['draft', 'active'])],

            'options' => ['array'],
            'options.*.option_id' => ['required', 'integer', 'exists:options,id'],

            'variants' => ['array', 'max:300'],
            'variants.*.option_values' => ['required', 'array', 'min:1'],
            'variants.*.option_values.*.option_id' => ['required', 'integer', 'exists:options,id'],
            'variants.*.option_values.*.option_value_id' => ['required', 'integer', 'exists:option_values,id'],
            'variants.*.sku' => ['nullable', 'string', 'max:100', 'distinct'],
            'variants.*.price_cents' => ['nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['nullable', 'string', 'size:3'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.is_active' => ['boolean'],

            'attributes' => ['array'],
            'attributes.*.attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'attributes.*.attribute_value_id' => ['required', 'integer', 'exists:attribute_values,id'],

            // (either url or file via multipart)
            'color_images' => ['array'],
            'color_images.*.option_value_id' => ['required', 'integer', 'exists:option_values,id'],
            'color_images.*.url' => ['nullable', 'string', 'url'],
            'color_images.*.file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'color_images.*.position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $payload = $this->all();

            if (!empty($payload['variants'])) {
                $fromVariants = collect($payload['variants'])
                    ->flatMap(fn ($vr) => collect($vr['option_values'] ?? [])->pluck('option_id'))
                    ->unique()->values()->all();

                $fromOptions = collect($payload['options'] ?? [])->pluck('option_id')->unique()->values()->all();

                if (!empty($fromOptions) && array_values($fromOptions) !== array_values($fromVariants)) {
                    $v->errors()->add('options', 'options set must match the option_ids used in variants.');
                }
            }
        });
    }
}
