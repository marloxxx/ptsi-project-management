<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'full_name',
        'position',
        'nik',
        'phone',
        'avatar',
        'date_of_birth',
        'gender',
        'employee_status',
        'position_level',
        'place_of_birth',
        'status',
        'unit_id',
        'preferred_language',
        'city',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
        ];
    }

    /**
     * Determine if the user can access Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true; // Customize based on your needs
    }

    /**
     * Determine if the user can impersonate other users.
     */
    public function canImpersonate(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Determine if the user can be impersonated.
     */
    public function canBeImpersonated(): bool
    {
        return ! $this->hasRole('super_admin');
    }

    /**
     * Activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'username',
                'email',
                'full_name',
                'position',
                'nik',
                'phone',
                'date_of_birth',
                'gender',
                'employee_status',
                'position_level',
                'place_of_birth',
                'status',
                'unit_id',
                'preferred_language',
                'city',
                'address',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the unit associated with the user.
     *
     * @return BelongsTo<Unit>
     *
     * @phpstan-return BelongsTo<Unit, static>
     */
    public function unit(): BelongsTo
    {
        /** @var BelongsTo<Unit, static> $relation */
        $relation = $this->belongsTo(Unit::class);

        return $relation;
    }
}
