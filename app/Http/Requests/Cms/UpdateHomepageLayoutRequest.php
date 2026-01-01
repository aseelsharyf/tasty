<?php

namespace App\Http\Requests\Cms;

use App\Services\Layouts\SectionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateHomepageLayoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by route middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $registry = app(SectionRegistry::class);
        $validTypes = implode(',', array_keys($registry->all()));

        return [
            'sections' => ['required', 'array'],
            'sections.*.id' => ['required', 'string', 'uuid'],
            'sections.*.type' => ['required', 'string', "in:{$validTypes}"],
            'sections.*.order' => ['required', 'integer', 'min:0'],
            'sections.*.enabled' => ['required', 'boolean'],
            'sections.*.config' => ['required', 'array'],
            'sections.*.dataSource' => ['required', 'array'],
            'sections.*.dataSource.action' => ['required', 'string'],
            'sections.*.dataSource.params' => ['nullable', 'array'],
            'sections.*.slots' => ['nullable', 'array'],
            'sections.*.slots.*.index' => ['required', 'integer', 'min:0'],
            'sections.*.slots.*.mode' => ['required', 'string', 'in:dynamic,manual,static'],
            'sections.*.slots.*.postId' => ['nullable', 'integer', 'exists:posts,id'],
            'sections.*.slots.*.product' => ['nullable', 'array'],
            'sections.*.slots.*.product.title' => ['nullable', 'string', 'max:255'],
            'sections.*.slots.*.product.description' => ['nullable', 'string', 'max:1000'],
            'sections.*.slots.*.product.image' => ['nullable', 'string', 'max:500'],
            'sections.*.slots.*.product.imageAlt' => ['nullable', 'string', 'max:255'],
            'sections.*.slots.*.product.tags' => ['nullable', 'array'],
            'sections.*.slots.*.product.url' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateNoDuplicatePosts($validator);
        });
    }

    /**
     * Validate that no post is assigned to multiple slots.
     */
    protected function validateNoDuplicatePosts(Validator $validator): void
    {
        $sections = $this->input('sections', []);
        $assignedPosts = [];

        foreach ($sections as $sectionIndex => $section) {
            $slots = $section['slots'] ?? [];

            foreach ($slots as $slotIndex => $slot) {
                if (($slot['mode'] ?? '') === 'manual' && ! empty($slot['postId'])) {
                    $postId = $slot['postId'];

                    if (isset($assignedPosts[$postId])) {
                        $validator->errors()->add(
                            "sections.{$sectionIndex}.slots.{$slotIndex}.postId",
                            'This post is already assigned to another slot.'
                        );
                    } else {
                        $assignedPosts[$postId] = [
                            'section' => $sectionIndex,
                            'slot' => $slotIndex,
                        ];
                    }
                }
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sections.required' => 'At least one section is required.',
            'sections.*.id.uuid' => 'Each section must have a valid UUID.',
            'sections.*.type.in' => 'Invalid section type specified.',
            'sections.*.slots.*.postId.exists' => 'The selected post does not exist.',
        ];
    }
}
