<?php

namespace App\Http\Requests\Cms;

use App\Models\Post;
use App\Models\Setting;
use App\Services\PostTemplateRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $post = $this->route('post');

        // User has full edit permission
        if ($user->can('posts.edit')) {
            return true;
        }

        // User has edit-own permission and is the author
        if ($user->can('posts.edit-own') && $post) {
            // Handle both Post model and UUID string from route
            $authorId = $post instanceof Post ? $post->author_id : Post::where('uuid', $post)->value('author_id');

            if ($authorId && (int) $authorId === (int) $user->id) {
                return true;
            }
        }

        return false;
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
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('posts', 'slug')->ignore($this->route('post')),
            ],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'content' => ['nullable', 'array'],
            'post_type' => ['nullable', Rule::in($validPostTypes)],
            'template' => ['nullable', 'string', 'max:50', function ($attribute, $value, $fail) {
                if ($value && ! PostTemplateRegistry::exists($value)) {
                    $fail('The selected template is invalid.');
                }
            }],
            // Status is controlled by workflow, not direct form submission
            'status' => ['nullable', Rule::in([
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
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media_items,id'],
            'cover_video_id' => ['nullable', 'integer', 'exists:media_items,id'],
            'featured_image_anchor' => ['nullable', 'array'],
            'featured_image_anchor.x' => ['required_with:featured_image_anchor', 'numeric', 'min:0', 'max:100'],
            'featured_image_anchor.y' => ['required_with:featured_image_anchor', 'numeric', 'min:0', 'max:100'],
            'remove_featured_image' => ['nullable', 'boolean'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'show_author' => ['nullable', 'boolean'],
            'author_id' => ['nullable', 'integer', 'exists:users,id'],
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
