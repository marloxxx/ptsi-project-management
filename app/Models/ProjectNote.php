<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectNote extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'created_by',
        'title',
        'body',
        'note_date',
    ];

    protected $casts = [
        'note_date' => 'date',
    ];

    /**
     * @return BelongsTo<Project, ProjectNote>
     */
    public function project(): BelongsTo
    {
        /** @var BelongsTo<Project, ProjectNote> $relation */
        $relation = $this->belongsTo(Project::class);

        return $relation;
    }

    /**
     * @return BelongsTo<User, ProjectNote>
     */
    public function author(): BelongsTo
    {
        /** @var BelongsTo<User, ProjectNote> $relation */
        $relation = $this->belongsTo(User::class, 'created_by');

        return $relation;
    }
}
