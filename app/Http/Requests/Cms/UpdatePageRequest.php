<?php

namespace App\Http\Requests\Cms;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('pages.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($pageId)],
            'content' => ['nullable', 'string'],
            'layout' => ['required', 'string', Rule::in(array_keys(Page::getLayouts()))],
            'status' => ['required', 'string', Rule::in(array_keys(Page::getStatuses()))],
            'is_blade' => ['boolean'],
            'editor_mode' => ['nullable', 'string', Rule::in(['code', 'markdown'])],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
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
            'title.required' => 'The page title is required.',
            'slug.required' => 'The page slug is required.',
            'slug.unique' => 'This slug is already in use.',
            'layout.in' => 'The selected layout is invalid.',
            'status.in' => 'The selected status is invalid.',
            'author_id.exists' => 'The selected author does not exist.',
        ];
    }
}
