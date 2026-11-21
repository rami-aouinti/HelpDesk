<?php

namespace App\Http\Resources;

use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TicketAttachment
 */
class TicketAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'download_url' => route('api.v1.tickets.attachments.download', [
                'ticket' => $this->ticket_id,
                'attachment' => $this->id,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
