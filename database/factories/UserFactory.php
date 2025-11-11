<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fullName = fake()->name();
        $usernameBase = Str::of($fullName)->slug('_');

        if ($usernameBase->isEmpty()) {
            $usernameBase = Str::of(Str::slug(fake()->unique()->userName(), '_'));
        }

        $username = $usernameBase
            ->limit(18, '')
            ->append('_'.fake()->unique()->numberBetween(100, 999));

        return [
            'name' => $fullName,
            'username' => (string) $username,
            'full_name' => $fullName,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'phone' => fake()->phoneNumber(),
            'avatar' => null,
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-20 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['Pria', 'Wanita']),
            'employee_status' => fake()->randomElement(['Tetap', 'Kontrak', 'Magang']),
            'position' => fake()->jobTitle(),
            'position_level' => fake()->randomElement(['Staff', 'Supervisor', 'Manager']),
            'place_of_birth' => fake()->city(),
            'status' => 'active',
            'preferred_language' => 'id',
            'nik' => fake()->unique()->regexify('[0-9]{16}'),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'unit_id' => Unit::factory(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
