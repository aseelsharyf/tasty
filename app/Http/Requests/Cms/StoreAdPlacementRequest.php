<?php

namespace App\Http\Requests\Cms;

use App\Models\AdPlacement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdPlacementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('settings.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'page_type' => ['required', 'string', Rule::in(array_keys(AdPlacement::getPageTypes()))],
            'slot' => ['required', 'string', Rule::in(array_keys(AdPlacement::SLOTS))],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'ad_code' => ['required', 'string', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
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
            'name.required' => 'The ad placement name is required.',
            'page_type.required' => 'Please select a page type.',
            'page_type.in' => 'The selected page type is invalid.',
            'slot.required' => 'Please select a slot.',
            'slot.in' => 'The selected slot is invalid.',
            'category_id.exists' => 'The selected category does not exist.',
            'ad_code.required' => 'The ad code is required.',
            'ad_code.max' => 'The ad code is too long.',
        ];
    }
}
