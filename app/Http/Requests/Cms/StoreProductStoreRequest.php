<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:product_stores,slug'],
            'business_type' => ['nullable', 'string', 'in:retail,distributor,restaurant'],
            'address' => ['nullable', 'string', 'max:1000'],
            'location_label' => ['nullable', 'string', 'max:255'],
            'logo_media_id' => ['nullable', 'integer', 'exists:media_items,id'],
            'hotline' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The store name is required.',
            'logo_media_id.exists' => 'The selected logo image does not exist.',
        ];
    }
}
