<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublishPostWithSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Admin', 'Editor', 'Developer']) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'versionUuid' => ['required', 'string', 'exists:content_versions,uuid'],
            'sectionId' => ['required', 'string', 'max:255'],
            'slotIndex' => ['required', 'integer', 'min:0'],
            'layoutType' => ['required', Rule::in(['homepage', 'category', 'tag'])],
            'pageLayoutId' => ['nullable', 'integer', 'exists:page_layouts,id'],
            'mode' => ['required', Rule::in(['immediate', 'scheduled'])],
            'scheduledAt' => [
                Rule::requiredIf($this->input('mode') === 'scheduled'),
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'scheduledAt.required' => 'Choose when this post should be published and assigned.',
            'scheduledAt.after' => 'The scheduled date and time must be in the future.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => $this->input('mode', 'immediate'),
        ]);
    }
}
