<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    /** @use HasFactory<\Database\Factories\UnitFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'sinav_unit_id',
        'status',
    ];

    /**
     * Get the users that belong to the unit.
     *
     * @return HasMany<User>
     *
     * @phpstan-return HasMany<User, static>
     */
    public function users(): HasMany
    {
        /** @var HasMany<User, static> $relation */
        $relation = $this->hasMany(User::class);

        return $relation;
    }

    /**
     * Scope a query to only include active units.
     *
     * @param  Builder<Unit>  $query
     * @return Builder<Unit>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query by status.
     *
     * @param  Builder<Unit>  $query
     * @return Builder<Unit>
     */
    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null) {
            return $query;
        }

        return $query->where('status', $status);
    }
}
