<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Commands\AddTicketMessageWithAttachments;

use App\ServiceDesk\Application\Contracts\TicketAttachmentStorage;
use App\ServiceDesk\Application\Contracts\TicketMessageCreatedPublisher;
use App\ServiceDesk\Application\Contracts\TicketMessageRepository;
use App\ServiceDesk\Application\Contracts\TransactionManager;
use Throwable;

final class AddTicketMessageWithAttachmentsHandler
{
    public function __construct(
        private readonly TicketMessageRepository $messages,
        private readonly TicketAttachmentStorage $storage,
        private readonly TicketMessageCreatedPublisher $publisher,
        private readonly TransactionManager $transactions,
    ) {
    }

    public function handle(AddTicketMessageWithAttachmentsCommand $command): int
    {
        $locations = [];

        try {
            $attachments = [];
            foreach ($command->attachments as $attachment) {
                $location = $this->storage->store($command->ticketId, $attachment->temporaryPath);
                $locations[] = $location;
                $attachments[] = [
                    ...$location,
                    'original_name' => mb_strcut(
                        basename(str_replace('\\\\', '/', $attachment->originalName)),
                        0,
                        255,
                        'UTF-8',
                    ),
                    'mime_type' => $attachment->mimeType,
                    'size' => $attachment->size,
                ];
            }

            $messageId = $this->transactions->run(fn (): int => $this->messages->createWithAttachments(
                $command->ticketId,
                $command->authorId,
                trim($command->body),
                $command->isInternal,
                $attachments,
            ));
        } catch (Throwable $exception) {
            if ($locations !== []) {
                $this->storage->delete($locations);
            }

            throw $exception;
        }

        $this->publisher->publish($messageId);

        return $messageId;
    }
}
