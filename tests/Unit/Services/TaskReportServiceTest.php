<?php

namespace Tests\Unit\Services;

use App\Repositories\TaskRepositoryInterface;
use App\Services\TaskReportService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class TaskReportServiceTest extends TestCase
{
    protected TaskRepositoryInterface $repository;
    protected TaskReportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TaskRepositoryInterface::class);
        $this->service = new TaskReportService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: generateReport() returns correct structure with mocked repository
     */
    public function test_generate_report_returns_correct_structure(): void
    {
        $startDate = now()->subMonth();
        $endDate = now();

        // Mock repository responses
        $this->repository->shouldReceive('getTasksInDateRange')
            ->once()
            ->with($startDate, $endDate, null)
            ->andReturn(new Collection());

        $this->repository->shouldReceive('getCategoryStatistics')
            ->once()
            ->andReturn([]);

        $this->repository->shouldReceive('getUserStatistics')
            ->once()
            ->andReturn([]);

        $this->repository->shouldReceive('getPriorityDistribution')
            ->once()
            ->andReturn([]);

        $this->repository->shouldReceive('getStatusDistribution')
            ->once()
            ->andReturn([]);

        $result = $this->service->generateReport($startDate, $endDate, null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('report', $result);
        $this->assertArrayHasKey('total_tasks', $result);
        $this->assertArrayHasKey('date_range', $result);
        $this->assertArrayHasKey('execution_time_ms', $result);
    }

    /**
     * Test: Service can be instantiated and tested without database
     */
    public function test_service_instantiation_without_database(): void
    {
        $this->assertInstanceOf(TaskReportService::class, $this->service);
    }
}
