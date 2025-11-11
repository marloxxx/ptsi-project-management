<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectNote>
 */
class ProjectNoteFactory extends Factory
{
    protected $model = ProjectNote::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'created_by' => User::factory(),
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraphs(2, true),
            'note_date' => $this->faker->date(),
        ];
    }
}
