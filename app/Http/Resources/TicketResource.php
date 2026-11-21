<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Ticket
 */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'estimated_minutes' => $this->estimated_minutes,
            'elapsed_minutes' => $this->elapsed_minutes,
            'remaining_minutes' => $this->remaining_minutes,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
            ]),
            'assigned_agent' => $this->whenLoaded('assignedAgent', fn () => $this->assignedAgent ? [
                'id' => $this->assignedAgent->id,
                'name' => $this->assignedAgent->name,
            ] : null),
            'team' => $this->whenLoaded('team', fn () => $this->team ? [
                'id' => $this->team->id,
                'name' => $this->team->name,
            ] : null),
            'project' => $this->whenLoaded('project', fn () => $this->project ? [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ] : null),
            'messages' => $this->whenLoaded('messages', fn () => $this->messages
                ->map(fn ($message) => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'is_internal' => $message->is_internal,
                    'author' => [
                        'id' => $message->user?->id,
                        'name' => $message->user?->name,
                    ],
                    'created_at' => $message->created_at?->toISOString(),
                ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
