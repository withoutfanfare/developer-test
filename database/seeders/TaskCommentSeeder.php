<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = Task::all();
        $users = User::all();
        
        $taskIds = $tasks->pluck('id')->toArray();
        $userIds = $users->pluck('id')->toArray();
        
        TaskComment::factory(25000)->create([
            'task_id' => fake()->randomElement($taskIds),
            'user_id' => fake()->randomElement($userIds),
        ]);
    }
}
