<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SavedFilter extends Model
{
    /** @use HasFactory<\Database\Factories\SavedFilterFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'name',
        'query',
        'visibility',
        'project_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'query' => 'array',
        ];
    }

    /**
     * Get the owner of the saved filter (polymorphic).
     *
     * @return MorphTo<User|Project, SavedFilter>
     */
    public function owner(): MorphTo
    {
        /** @var MorphTo<User|Project, SavedFilter> $relation */
        $relation = $this->morphTo();

        return $relation;
    }

    /**
     * Get the project associated with the saved filter.
     *
     * @return BelongsTo<Project, SavedFilter>
     */
    public function project(): BelongsTo
    {
        /** @var BelongsTo<Project, SavedFilter> $relation */
        $relation = $this->belongsTo(Project::class);

        return $relation;
    }
}
