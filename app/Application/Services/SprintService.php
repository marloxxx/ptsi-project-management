<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\SprintRepositoryInterface;
use App\Domain\Services\SprintServiceInterface;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SprintService implements SprintServiceInterface
{
    public function __construct(
        private readonly SprintRepositoryInterface $sprintRepository
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Project $project, array $data): Sprint
    {
        return DB::transaction(function () use ($project, $data) {
            // Use provided created_by if available, otherwise get from Auth
            if (! isset($data['created_by'])) {
                $userId = Auth::id();

                if ($userId === null) {
                    throw new RuntimeException('User must be authenticated to create sprint.');
                }

                $data['created_by'] = $userId;
            }

            $data['state'] = $data['state'] ?? 'Planned';

            $sprint = $this->sprintRepository->create($project, $data);

            activity()
                ->performedOn($sprint)
                ->event('created')
                ->log('Sprint created');

            return $sprint;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Sprint $sprint, array $data): Sprint
    {
        return DB::transaction(function () use ($sprint, $data) {
            $sprint = $this->sprintRepository->update($sprint, $data);

            activity()
                ->performedOn($sprint)
                ->event('updated')
                ->log('Sprint updated');

            return $sprint;
        });
    }

    public function activate(Sprint $sprint): Sprint
    {
        return DB::transaction(function () use ($sprint) {
            // Close any other active sprint in the same project
            $activeSprint = $this->sprintRepository->getActiveSprint($sprint->project);

            if ($activeSprint !== null && $activeSprint->getKey() !== $sprint->getKey()) {
                $this->close($activeSprint);
            }

            $sprint = $this->sprintRepository->update($sprint, [
                'state' => 'Active',
            ]);

            activity()
                ->performedOn($sprint)
                ->event('activated')
                ->log('Sprint activated');

            return $sprint;
        });
    }

    public function close(Sprint $sprint): Sprint
    {
        return DB::transaction(function () use ($sprint) {
            $sprint = $this->sprintRepository->update($sprint, [
                'state' => 'Closed',
                'closed_at' => Carbon::now(),
            ]);

            activity()
                ->performedOn($sprint)
                ->event('closed')
                ->log('Sprint closed');

            return $sprint;
        });
    }

    public function reopen(Sprint $sprint): Sprint
    {
        return DB::transaction(function () use ($sprint) {
            // Close any other active sprint in the same project
            $activeSprint = $this->sprintRepository->getActiveSprint($sprint->project);

            if ($activeSprint !== null && $activeSprint->getKey() !== $sprint->getKey()) {
                $this->close($activeSprint);
            }

            $sprint = $this->sprintRepository->update($sprint, [
                'state' => 'Active',
                'closed_at' => null,
            ]);

            activity()
                ->performedOn($sprint)
                ->event('reopened')
                ->log('Sprint reopened');

            return $sprint;
        });
    }

    /**
     * @param  array<int, int>  $ticketIds
     */
    public function assignTickets(Sprint $sprint, array $ticketIds): void
    {
        DB::transaction(function () use ($sprint, $ticketIds) {
            $this->sprintRepository->assignTickets($sprint, $ticketIds);

            activity()
                ->performedOn($sprint)
                ->withProperties(['ticket_ids' => $ticketIds])
                ->log('Tickets assigned to sprint');
        });
    }

    /**
     * Compute burndown data for a sprint.
     *
     * @return array<int, array{date: string, remaining: int, ideal: float}>
     */
    public function computeBurndown(Sprint $sprint): array
    {
        if ($sprint->start_date === null || $sprint->end_date === null) {
            return [];
        }

        $startDate = Carbon::parse($sprint->start_date);
        $endDate = Carbon::parse($sprint->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        if ($totalDays <= 0) {
            return [];
        }

        // Get all tickets in the sprint
        $tickets = $sprint->tickets()->with('status')->get();

        // Count total tickets (or story points if available)
        $totalTickets = $tickets->count();

        // Get completed status IDs
        $completedStatusIds = $sprint->project->ticketStatuses()
            ->where('is_completed', true)
            ->pluck('id')
            ->toArray();

        // Calculate remaining tickets per day
        $burndownData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Count tickets that were completed on or before this date
            $completedCount = $tickets->filter(function ($ticket) use ($currentDate, $completedStatusIds) {
                if (! in_array($ticket->ticket_status_id, $completedStatusIds, true)) {
                    return false;
                }

                $history = $ticket->histories()
                    ->where('ticket_status_id', $ticket->ticket_status_id)
                    ->whereDate('created_at', '<=', $currentDate)
                    ->exists();

                return $history;
            })->count();

            $remaining = max(0, $totalTickets - $completedCount);

            // Ideal burndown (linear)
            $daysElapsed = $startDate->diffInDays($currentDate) + 1;
            $ideal = max(0, $totalTickets * (1 - ($daysElapsed / $totalDays)));

            $burndownData[] = [
                'date' => $currentDate->format('Y-m-d'),
                'remaining' => $remaining,
                'ideal' => round($ideal, 2),
            ];

            $currentDate->addDay();
        }

        return $burndownData;
    }

    /**
     * Compute velocity (completed ticket count) for a sprint.
     */
    public function computeVelocity(Sprint $sprint): float
    {
        $completedStatusIds = $sprint->project->ticketStatuses()
            ->where('is_completed', true)
            ->pluck('id')
            ->toArray();

        $completedTickets = $sprint->tickets()
            ->whereIn('ticket_status_id', $completedStatusIds)
            ->count();

        return (float) $completedTickets;
    }

    /**
     * @return Collection<int, Sprint>
     */
    public function forProject(Project $project, ?string $state = null): Collection
    {
        return $this->sprintRepository->forProject($project, $state);
    }
}
