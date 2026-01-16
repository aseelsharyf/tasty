<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.create');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required'],
            'title.*' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable'],
            'description.*' => ['nullable', 'string', 'max:2000'],
            'product_category_id' => ['required', 'integer', 'exists:product_categories,id'],
            'featured_tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media_items,id'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['nullable', 'string', 'size:3'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'affiliate_url' => ['required', 'string', 'url', 'max:2048'],
            'affiliate_source' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The product title is required.',
            'slug.unique' => 'This slug is already in use.',
            'product_category_id.required' => 'Please select a category.',
            'product_category_id.exists' => 'The selected category does not exist.',
            'featured_media_id.exists' => 'The selected image does not exist.',
            'affiliate_url.required' => 'The affiliate URL is required.',
            'affiliate_url.url' => 'Please enter a valid URL.',
            'sku.unique' => 'This SKU is already in use.',
        ];
    }
}
