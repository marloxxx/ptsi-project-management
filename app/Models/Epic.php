<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Epic extends Model
{
    /** @use HasFactory<\Database\Factories\EpicFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Project, Epic>
     */
    public function project(): BelongsTo
    {
        /** @var BelongsTo<Project, Epic> $relation */
        $relation = $this->belongsTo(Project::class);

        return $relation;
    }

    /**
     * @return HasMany<Ticket, Epic>
     */
    public function tickets(): HasMany
    {
        /** @var HasMany<Ticket, Epic> $relation */
        $relation = $this->hasMany(Ticket::class);

        return $relation;
    }
}
