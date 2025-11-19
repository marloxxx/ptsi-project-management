<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectCustomField extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectCustomFieldFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'key',
        'label',
        'type',
        'options',
        'required',
        'order',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
            'order' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Project, ProjectCustomField>
     */
    public function project(): BelongsTo
    {
        /** @var BelongsTo<Project, ProjectCustomField> $relation */
        $relation = $this->belongsTo(Project::class);

        return $relation;
    }

    /**
     * @return HasMany<TicketCustomValue, ProjectCustomField>
     */
    public function ticketCustomValues(): HasMany
    {
        /** @var HasMany<TicketCustomValue, ProjectCustomField> $relation */
        $relation = $this->hasMany(TicketCustomValue::class, 'custom_field_id');

        return $relation;
    }
}
