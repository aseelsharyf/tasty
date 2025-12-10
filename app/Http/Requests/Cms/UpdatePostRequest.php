<?php

namespace App\Http\Requests\Cms;

use App\Models\Post;
use App\Services\PostTemplateRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('posts.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts', 'slug')->ignore($this->route('post')),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'array'],
            'post_type' => ['nullable', Rule::in([Post::TYPE_ARTICLE, Post::TYPE_RECIPE])],
            'template' => ['nullable', 'string', 'max:50', function ($attribute, $value, $fail) {
                if ($value && ! PostTemplateRegistry::exists($value)) {
                    $fail('The selected template is invalid.');
                }
            }],
            // Status is controlled by workflow, not direct form submission
            'status' => ['nullable', Rule::in([
                Post::STATUS_DRAFT,
                Post::STATUS_PENDING,
                Post::STATUS_PUBLISHED,
                Post::STATUS_SCHEDULED,
            ])],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media_items,id'],
            'remove_featured_image' => ['nullable', 'boolean'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'show_author' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a title for your post.',
            'slug.unique' => 'This slug is already in use. Please choose a different one.',
            'scheduled_at.after' => 'Scheduled date must be in the future.',
            'featured_image.max' => 'Featured image must be less than 5MB.',
            'meta_title.max' => 'SEO title should be less than 255 characters.',
            'meta_description.max' => 'SEO description should be less than 500 characters.',
        ];
    }
}
