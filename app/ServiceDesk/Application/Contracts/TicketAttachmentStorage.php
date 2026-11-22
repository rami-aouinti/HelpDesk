<?php

declare(strict_types=1);

namespace App\ServiceDesk\Application\Contracts;

interface TicketAttachmentStorage
{
    /**
     * @return array{disk: string, path: string}
     */
    public function store(int $ticketId, string $temporaryPath): array;

    public function exists(string $disk, string $path): bool;

    /** @return resource|false */
    public function readStream(string $disk, string $path);

    /**
     * @param array<array{disk: string, path: string}> $locations
     */
    public function delete(array $locations): void;
}
