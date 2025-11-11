<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Unit';

        return [
            'name' => $name,
            'code' => Str::upper(Str::random(4)),
            'sinav_unit_id' => fake()->optional()->bothify('SNV-####'),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
