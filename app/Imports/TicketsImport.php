<?php

declare(strict_types=1);

namespace App\Imports;

use App\Domain\Services\TicketServiceInterface;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TicketsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public function __construct(private readonly bool $allowUpdates = false) {}

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        /** @var TicketServiceInterface $ticketService */
        $ticketService = App::make(TicketServiceInterface::class);

        $rows->each(function (array $row) use ($ticketService): void {
            /** @var array<string, mixed> $row */
            $payload = $this->normalizeRow($row);

            if (! $this->isValidPayload($payload)) {
                Log::warning('Skipping ticket import row due to missing required fields.', $row);

                return;
            }

            $assigneeIds = $payload['assignee_ids'];
            unset($payload['assignee_ids']);

            $ticketId = $payload['id'] ?? null;
            unset($payload['id']);

            if ($this->allowUpdates && $ticketId !== null && Ticket::query()->whereKey($ticketId)->exists()) {
                $ticketService->update($ticketId, $payload, $assigneeIds);

                return;
            }

            $ticketService->create($payload, $assigneeIds);
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $projectId = $this->castInt($row['project_id'] ?? null);
        $statusId = $this->castInt($row['ticket_status_id'] ?? null);
        $priorityId = $this->castInt($row['priority_id'] ?? null);
        $epicId = $this->castInt($row['epic_id'] ?? null);
        $assigneeIds = $this->parseAssigneeIds($row['assignee_ids'] ?? null);

        return [
            'id' => $this->castInt($row['id'] ?? null),
            'project_id' => $projectId,
            'ticket_status_id' => $statusId,
            'priority_id' => $priorityId,
            'epic_id' => $epicId,
            'name' => $row['name'] ?? null,
            'description' => $row['description'] ?? null,
            'start_date' => $this->castDate($row['start_date'] ?? null),
            'due_date' => $this->castDate($row['due_date'] ?? null),
            'assignee_ids' => $assigneeIds,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isValidPayload(array $payload): bool
    {
        $projectId = $payload['project_id'] ?? null;
        $statusId = $payload['ticket_status_id'] ?? null;
        $priorityId = $payload['priority_id'] ?? null;
        $name = $payload['name'] ?? null;

        return $projectId !== null
            && $statusId !== null
            && $priorityId !== null
            && $name !== null;
    }

    private function castInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function castDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, int>
     */
    private function parseAssigneeIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return collect($value)
                ->filter(fn ($id): bool => is_numeric($id))
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        return collect(explode(',', (string) $value))
            ->map(fn (string $id): string => trim($id))
            ->filter(fn (string $id): bool => is_numeric($id))
            ->map(fn (string $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
