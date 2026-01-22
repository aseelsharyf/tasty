<?php

namespace App\Http\Requests;

use App\Models\RecipeSubmission;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeSubmissionRequest extends FormRequest
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
        $rules = [
            // Submission type
            'submission_type' => ['required', 'in:single,composite'],

            // Submitter info
            'submitter_name' => ['required', 'string', 'max:255'],
            'submitter_email' => ['required', 'email', 'max:255'],
            'submitter_phone' => ['nullable', 'string', 'max:50'],
            'is_chef' => ['required', 'boolean'],
            'chef_name' => ['required_if:is_chef,false', 'nullable', 'string', 'max:255'],

            // Recipe basic info
            'recipe_name' => ['required', 'string', 'max:255'],
            'headline' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'prep_time' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'cook_time' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'servings' => ['nullable', 'integer', 'min:1', 'max:999'],

            // Categories (optional)
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string'],

            // Meal times (optional)
            'meal_times' => ['nullable', 'array'],
            'meal_times.*' => ['string'],

            // Ingredients (grouped)
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.group_name' => ['nullable', 'string', 'max:255'],
            'ingredients.*.items' => ['required', 'array', 'min:1'],
            'ingredients.*.items.*.ingredient' => ['required', 'string', 'max:255'],
            'ingredients.*.items.*.quantity' => ['nullable', 'string', 'max:50'],

            // Instructions (grouped)
            'instructions' => ['required', 'array', 'min:1'],
            'instructions.*.group_name' => ['nullable', 'string', 'max:255'],
            'instructions.*.steps' => ['required', 'array', 'min:1'],
            'instructions.*.steps.*' => ['required', 'string', 'max:2000'],

            // Image
            'image' => ['nullable', 'image', 'max:10240'], // 10MB max

            // Submitter avatar
            'submitter_avatar' => ['nullable', 'image', 'max:2048'], // 2MB max
        ];

        // Additional rules for composite meals
        if ($this->input('submission_type') === RecipeSubmission::TYPE_COMPOSITE) {
            $rules['child_recipes'] = ['required', 'array', 'min:2'];
            $rules['child_recipes.*.recipe_name'] = ['required', 'string', 'max:255'];
            $rules['child_recipes.*.description'] = ['required', 'string', 'max:2000'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'submitter_name.required' => 'Please enter your name.',
            'submitter_email.required' => 'Please enter your email address.',
            'submitter_email.email' => 'Please enter a valid email address.',
            'chef_name.required_if' => 'Please enter the chef\'s name.',
            'recipe_name.required' => 'Please enter a recipe name.',
            'headline.required' => 'Please enter a headline for your recipe.',
            'description.required' => 'Please add a description for your recipe.',
            'ingredients.required' => 'Please add at least one ingredient.',
            'ingredients.min' => 'Please add at least one ingredient.',
            'ingredients.*.items.required' => 'Each ingredient group must have at least one ingredient.',
            'ingredients.*.items.*.ingredient.required' => 'Please specify the ingredient name.',
            'instructions.required' => 'Please add at least one instruction step.',
            'instructions.min' => 'Please add at least one instruction step.',
            'instructions.*.steps.required' => 'Each instruction group must have at least one step.',
            'instructions.*.steps.*.required' => 'Please fill in the instruction step.',
            'image.image' => 'Please upload a valid image file.',
            'image.max' => 'The image must be less than 10MB.',
            'child_recipes.required' => 'Composite meals must have at least 2 recipes.',
            'child_recipes.min' => 'Composite meals must have at least 2 recipes.',
        ];
    }
}
