<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $ticket = $this->route('ticket');

        if (! $user || ! $ticket instanceof Ticket || ! $user->can('view', $ticket)) {
            return false;
        }

        return ! $this->boolean('is_internal') || $user->can('update', $ticket);
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'required_without:attachments', 'string', 'min:2', 'max:10000'],
            'is_internal' => ['sometimes', 'boolean'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,jpg,jpeg,png,txt,csv,doc,docx,xls,xlsx',
                'extensions:pdf,jpg,jpeg,png,txt,csv,doc,docx,xls,xlsx',
            ],
        ];
    }
}
