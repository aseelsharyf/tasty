<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class StoreMenuRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('menus.create');
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
            'location' => ['required', 'string', 'max:100', 'unique:menus,location', 'regex:/^[a-z0-9_-]+$/'],
            'description' => ['nullable'],
            'description.*' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
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
            'name.required' => 'The menu name is required.',
            'location.required' => 'The menu location is required.',
            'location.unique' => 'This location is already in use by another menu.',
            'location.regex' => 'The location may only contain lowercase letters, numbers, dashes and underscores.',
        ];
    }
}
