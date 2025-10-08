<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by auth:sanctum middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_date' => [
                'sometimes',
                'date',
                'before_or_equal:end_date',
            ],
            'end_date' => [
                'sometimes',
                'date',
                'after_or_equal:start_date',
            ],
            'user_filter' => [
                'sometimes',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.date' => 'The start date must be a valid date format (Y-m-d).',
            'start_date.before_or_equal' => 'The start date must be before or equal to the end date.',
            'end_date.date' => 'The end date must be a valid date format (Y-m-d).',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'user_filter.string' => 'The user filter must be a text string.',
            'user_filter.max' => 'The user filter must not exceed 255 characters.',
        ];
    }
}
