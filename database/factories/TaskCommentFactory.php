<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskComment>
 */
class TaskCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'comment' => $this->faker->paragraph($this->faker->numberBetween(1, 4)),
            'attachments' => $this->faker->optional(0.2)->randomElements([
                ['filename' => $this->faker->word . '.pdf', 'size' => $this->faker->numberBetween(1000, 50000)],
                ['filename' => $this->faker->word . '.png', 'size' => $this->faker->numberBetween(5000, 200000)],
                ['filename' => $this->faker->word . '.docx', 'size' => $this->faker->numberBetween(2000, 100000)],
            ], $this->faker->numberBetween(1, 2), false),
            'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
