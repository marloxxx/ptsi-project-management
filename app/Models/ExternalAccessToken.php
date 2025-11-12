<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalAccessToken extends Model
{
    /** @use HasFactory<\Database\Factories\ExternalAccessTokenFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'access_token',
        'password',
        'is_active',
        'last_accessed_at',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Project, ExternalAccessToken>
     */
    public function project(): BelongsTo
    {
        /** @var BelongsTo<Project, ExternalAccessToken> $relation */
        $relation = $this->belongsTo(Project::class);

        return $relation;
    }
}
