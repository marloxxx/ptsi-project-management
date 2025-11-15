<?php

namespace Database\Factories;

use App\Models\ExternalAccessToken;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ExternalAccessToken>
 */
class ExternalAccessTokenFactory extends Factory
{
    protected $model = ExternalAccessToken::class;

    public function definition(): array
    {
        $password = Str::random(16);

        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->company(),
            'access_token' => Str::uuid()->toString(),
            'password' => bcrypt($password),
            'is_active' => true,
            'last_accessed_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
