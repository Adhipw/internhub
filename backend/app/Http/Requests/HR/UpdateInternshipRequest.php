<?php

namespace App\Http\Requests\HR;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInternshipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Handled by policy in controller
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'benefits' => 'nullable|string',
            'type' => 'required|in:WFH,Office,Hybrid',
            'location' => 'required_if:type,Office,Hybrid|nullable|string',
            'salary_range' => 'nullable|string',
            'deadline_at' => 'nullable|date',
            'status' => 'required|in:draft,published,closed',
        ];
    }
}
