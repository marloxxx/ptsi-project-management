<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectWorkflow extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectWorkflowFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'definition',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'definition' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Project, ProjectWorkflow>
     */
    public function project(): BelongsTo
    {
        /** @var BelongsTo<Project, ProjectWorkflow> $relation */
        $relation = $this->belongsTo(Project::class);

        return $relation;
    }

    /**
     * Check if a transition from one status to another is allowed.
     */
    public function isTransitionAllowed(?int $fromStatusId, int $toStatusId): bool
    {
        $definition = $this->definition ?? [];

        // If no workflow defined, allow all transitions (backward compatible)
        if (empty($definition)) {
            return true;
        }

        // Check if this specific transition is defined
        $transitions = $definition['transitions'] ?? [];

        // If fromStatusId is null (ticket creation), check initial statuses
        if ($fromStatusId === null) {
            $initialStatuses = $definition['initial_statuses'] ?? [];

            return in_array($toStatusId, $initialStatuses, true);
        }

        // Check if transition exists: from_status_id => [to_status_ids]
        $allowedToStatuses = $transitions[(string) $fromStatusId] ?? [];

        return in_array($toStatusId, $allowedToStatuses, true);
    }

    /**
     * Get all allowed target status IDs for a given source status.
     *
     * @return array<int>
     */
    public function getAllowedTargetStatuses(?int $fromStatusId): array
    {
        $definition = $this->definition ?? [];

        if (empty($definition)) {
            return [];
        }

        if ($fromStatusId === null) {
            return $definition['initial_statuses'] ?? [];
        }

        $transitions = $definition['transitions'] ?? [];

        return $transitions[(string) $fromStatusId] ?? [];
    }
}
