<?php

namespace App\\Http\\Requests;

use App\\Models\\Ticket;
use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Ticket::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high', 'urgent'])],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'The ticket subject is required.',
            'subject.min' => 'The subject must contain at least 5 characters.',
            'description.required' => 'Please describe your problem.',
            'description.min' => 'The description must contain at least 10 characters.',
            'priority.required' => 'Please select a priority.',
        ];
    }
}
