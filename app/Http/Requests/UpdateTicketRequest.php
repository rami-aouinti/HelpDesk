<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('ticket')) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'sometimes',
                'required',
                Rule::in(['open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed']),
            ],
            'priority' => [
                'sometimes',
                'required',
                Rule::in(['low', 'normal', 'high', 'urgent']),
            ],
            'team_id' => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
