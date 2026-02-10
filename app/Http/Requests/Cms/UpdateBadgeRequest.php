<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBadgeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('badges.edit');
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
            'slug' => ['nullable', 'string', 'max:255', 'unique:badges,slug,'.$this->route('badge')->id],
            'icon' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'string', 'max:50'],
            'description' => ['nullable'],
            'description.*' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'order' => ['integer', 'min:0'],
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
            'name.required' => 'The badge name is required.',
            'slug.unique' => 'This slug is already in use.',
        ];
    }
}
