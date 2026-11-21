<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user || ! $user->can('update', $this->route('ticket'))) {
            return false;
        }

        // Assignment and reassignment are administrative responsibilities.
        return !$this->exists('team_id') && !$this->exists('assigned_to') || $user->hasRole('admin');
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
            'project_id' => ['sometimes', 'nullable', 'integer', 'exists:projects,id'],
            'estimated_minutes' => ['sometimes', 'required', 'integer', 'min:0', 'max:525600'],
            'elapsed_minutes' => ['sometimes', 'required', 'integer', 'min:0', 'max:525600'],
            'team_id' => ['sometimes', 'nullable', 'integer', 'exists:teams,id'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ];
    }
}
