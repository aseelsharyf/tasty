<?php

namespace App\Http\Requests\Cms;

use App\Models\Language;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuickDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('posts.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $postTypes = Setting::getPostTypes();
        $validPostTypes = array_column($postTypes, 'slug');

        $validLanguages = Language::active()->pluck('code')->toArray();

        return [
            'title' => ['nullable', 'string', 'max:70'],
            'post_type' => ['required', Rule::in($validPostTypes)],
            'language_code' => ['required', Rule::in($validLanguages)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Title should be 70 characters or less.',
            'post_type.required' => 'Please select an article type.',
            'post_type.in' => 'Invalid article type selected.',
            'language_code.required' => 'Please select a language.',
            'language_code.in' => 'Invalid language selected.',
        ];
    }
}
