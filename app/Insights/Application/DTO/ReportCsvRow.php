<?php

declare(strict_types=1);

namespace App\Insights\Application\DTO;

final readonly class ReportCsvRow
{
    public function __construct(
        public int $id,
        public string $subject,
        public string $status,
        public string $priority,
        public ?string $createdAt,
        public ?string $customer,
        public ?string $agent,
        public ?string $team,
        public ?string $project,
        public int $trackedMinutes,
    ) {
    }

    /**
     * @return array<int, int|string>
     */
    public function toCsv(): array
    {
        return [
            $this->id,
            $this->subject,
            $this->status,
            $this->priority,
            $this->createdAt ?? '',
            $this->customer ?? '',
            $this->agent ?? '',
            $this->team ?? '',
            $this->project ?? '',
            $this->trackedMinutes,
        ];
    }
}
