<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory(50)->create();
        
        $userIds = $users->pluck('id')->toArray();
        
        Task::factory(10000)->create([
            'user_id' => fake()->randomElement($userIds),
            'assigned_to' => fake()->optional(0.7)->randomElement($userIds),
        ]);
    }
}
