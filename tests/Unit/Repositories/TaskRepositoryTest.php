<?php

namespace Tests\Unit\Repositories;

use App\Models\Task;
use App\Models\User;
use App\Repositories\TaskRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected TaskRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new TaskRepository;
    }

    /**
     * Test: getTasksInDateRange() returns filtered tasks
     */
    public function test_get_tasks_in_date_range_returns_filtered_tasks(): void
    {
        $user = User::factory()->create();

        // Create tasks with different dates
        Task::factory()->create(['created_at' => now()->subDays(10), 'user_id' => $user->id]);
        Task::factory()->create(['created_at' => now()->subDays(5), 'user_id' => $user->id]);
        Task::factory()->create(['created_at' => now()->subMonths(2), 'user_id' => $user->id]);

        $startDate = now()->subDays(7);
        $endDate = now();

        $tasks = $this->repository->getTasksInDateRange($startDate, $endDate, null);

        $this->assertCount(1, $tasks);
    }

    /**
     * Test: User filter applies correct whereHas clause
     */
    public function test_user_filter_applies_correctly(): void
    {
        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);

        Task::factory()->create(['user_id' => $user1->id, 'created_at' => now()]);
        Task::factory()->create(['user_id' => $user2->id, 'created_at' => now()]);

        $tasks = $this->repository->getTasksInDateRange(
            now()->subDay(),
            now()->addDay(),
            'John'
        );

        $this->assertCount(1, $tasks);
        $this->assertEquals('John Doe', $tasks->first()->user->name);
    }

    /**
     * Test: Eager loading relationships included
     */
    public function test_eager_loading_relationships_included(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'created_at' => now()]);

        $tasks = $this->repository->getTasksInDateRange(now()->subDay(), now()->addDay(), null);

        // Verify relationships are eager loaded (no additional queries)
        $this->assertTrue($tasks->first()->relationLoaded('user'));
        $this->assertTrue($tasks->first()->relationLoaded('assignedTo'));
        $this->assertTrue($tasks->first()->relationLoaded('comments'));
    }
}
