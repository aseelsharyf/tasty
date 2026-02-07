<?php

namespace App\Http\Requests\Cms;

use App\Models\Post;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
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
        // Get valid post type slugs from settings
        $postTypes = Setting::getPostTypes();
        $validPostTypes = array_column($postTypes, 'slug');

        return [
            'title' => ['nullable', 'string', 'max:70'],
            'kicker' => ['nullable', 'string', 'max:100'],
            'subtitle' => ['nullable', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:posts,slug'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'content' => ['nullable', 'array'],
            'post_type' => ['required', Rule::in($validPostTypes)],
            'status' => ['required', Rule::in([
                Post::STATUS_DRAFT,
                Post::STATUS_PUBLISHED,
                Post::STATUS_SCHEDULED,
            ])],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'featured_tag_id' => ['required', 'integer', 'exists:tags,id'],
            'sponsor_id' => ['nullable', 'integer', 'exists:sponsors,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'featured_image' => ['nullable', 'image', 'max:5120'], // 5MB
            'featured_media_id' => ['nullable', 'integer', 'exists:media_items,id'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:160'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Headline should be 70 characters or less.',
            'subtitle.max' => 'Subheading should be 120 characters or less.',
            'excerpt.max' => 'Summary should be 300 characters or less.',
            'slug.unique' => 'This slug is already in use. Please choose a different one.',
            'scheduled_at.after' => 'Scheduled date must be in the future.',
            'featured_image.max' => 'Featured image must be less than 5MB.',
            'meta_title.max' => 'SEO title should be less than 70 characters.',
            'meta_description.max' => 'SEO description should be less than 160 characters.',
            'category_id.required' => 'Please select a category.',
            'featured_tag_id.required' => 'Please select a featured tag.',
        ];
    }
}
