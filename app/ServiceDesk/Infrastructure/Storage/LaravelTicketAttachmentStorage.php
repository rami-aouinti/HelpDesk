<?php

declare(strict_types=1);

namespace App\ServiceDesk\Infrastructure\Storage;

use App\ServiceDesk\Application\Contracts\TicketAttachmentStorage;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class LaravelTicketAttachmentStorage implements TicketAttachmentStorage
{
    public function store(int $ticketId, string $temporaryPath): array
    {
        $path = Storage::disk('local')->putFile("ticket-attachments/{$ticketId}", new File($temporaryPath));

        if ($path === false) {
            throw new RuntimeException('The attachment could not be stored.');
        }

        return ['disk' => 'local', 'path' => $path];
    }

    public function exists(string $disk, string $path): bool
    {
        return Storage::disk($disk)->exists($path);
    }

    /** @return resource|null */
    public function readStream(string $disk, string $path)
    {
        return Storage::disk($disk)->readStream($path);
    }

    public function delete(array $locations): void
    {
        foreach ($locations as $location) {
            Storage::disk($location['disk'])->delete($location['path']);
        }
    }
}
