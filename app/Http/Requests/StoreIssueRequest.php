<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use App\Enums\Status;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'priority' => ['required', 'string', Rule::enum(Priority::class)],
            'category' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(Status::class)],
            'due_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
