<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('collections.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $collectionId = $this->route('collection')->id;

        return [
            'name' => ['required'],
            'name.*' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable'],
            'description.*' => ['nullable', 'string', 'max:1000'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('collections', 'slug')->ignore($collectionId)],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'string', 'in:manual,recent,oldest,most_viewed,title_asc,title_desc'],
            'posts' => ['nullable', 'array'],
            'posts.*' => ['integer', 'exists:posts,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
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
            'name.required' => 'The collection name is required.',
            'slug.unique' => 'This slug is already in use.',
        ];
    }
}
