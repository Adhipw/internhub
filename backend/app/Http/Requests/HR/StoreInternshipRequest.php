<?php

namespace App\Http\Requests\HR;

use Illuminate\Foundation\Http\FormRequest;

class StoreInternshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handled by policy
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
            'deadline_at' => 'nullable|date|after:today',
            'status' => 'required|in:draft,published,closed',
        ];
    }
}
