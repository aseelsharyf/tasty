<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSponsorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sponsors.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'name.*' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('sponsors', 'slug')->ignore($this->route('sponsor')),
            ],
            'url' => ['nullable'],
            'url.*' => ['nullable', 'string', 'url', 'max:500'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media_items,id'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The sponsor name is required.',
            'slug.unique' => 'This slug is already in use.',
            'url.*.url' => 'The URL must be a valid URL.',
            'featured_media_id.exists' => 'The selected image does not exist.',
        ];
    }
}
