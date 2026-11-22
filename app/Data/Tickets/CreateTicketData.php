<?php

declare(strict_types=1);

namespace App\Data\Tickets;

use App\Models\User;

final readonly class CreateTicketData
{
    public function __construct(
        public string $subject,
        public string $description,
        public string $priority,
        public ?int $projectId,
        public int $customerId,
    ) {
    }

    /**
     * @param array{subject: string, description: string, priority: string, project_id?: int|string|null} $data
     */
    public static function fromValidated(array $data, User $customer): self
    {
        return new self(
            subject: $data['subject'],
            description: $data['description'],
            priority: $data['priority'],
            projectId: isset($data['project_id']) ? (int) $data['project_id'] : null,
            customerId: (int) $customer->getKey(),
        );
    }
}
