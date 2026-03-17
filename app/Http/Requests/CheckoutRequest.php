<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'delivery_location_id' => ['nullable', 'integer', 'exists:delivery_locations,id'],
            'address' => ['required', 'string', 'max:1000'],
            'contact_person' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'additional_info' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'address.required' => 'Please enter your delivery address.',
            'contact_person.required' => 'Please enter the contact person name.',
            'contact_number.required' => 'Please enter a contact number.',
        ];
    }
}
