<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Development', 'Design', 'Testing', 'Documentation', 'Bug Fix', 'Research', 'Meeting', 'Review'];
        $statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        $priorities = ['low', 'medium', 'high', 'urgent'];
        
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(3),
            'status' => $this->faker->randomElement($statuses),
            'priority' => $this->faker->randomElement($priorities),
            'user_id' => User::factory(),
            'assigned_to' => $this->faker->optional(0.7)->randomElement(User::pluck('id')->toArray() ?: [1]),
            'due_date' => $this->faker->optional(0.6)->dateTimeBetween('now', '+3 months'),
            'metadata' => $this->faker->optional(0.4)->randomElements([
                'client' => $this->faker->company,
                'project' => $this->faker->words(2, true),
                'tags' => $this->faker->words(3),
                'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
                'environment' => $this->faker->randomElement(['development', 'staging', 'production']),
            ], $this->faker->numberBetween(1, 3), false),
            'estimated_hours' => $this->faker->optional(0.7)->randomFloat(2, 0.5, 40),
            'actual_hours' => $this->faker->optional(0.5)->randomFloat(2, 0.5, 50),
            'category' => $this->faker->randomElement($categories),
            'notes' => $this->faker->optional(0.3)->paragraph(),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
