<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\TaskReportResource;
use Tests\TestCase;

class TaskReportResourceTest extends TestCase
{
    /**
     * Test: Resource formats response correctly
     */
    public function test_resource_formats_response_correctly(): void
    {
        $reportData = [
            'report' => [
                ['task_id' => 1, 'title' => 'Test Task'],
            ],
            'total_tasks' => 1,
            'date_range' => [
                'start' => '2025-01-01',
                'end' => '2025-01-31',
            ],
            'user_filter' => 'John',
            'generated_at' => '2025-01-31T12:00:00+00:00',
            'cached' => false,
            'execution_time_ms' => 123.45,
            'memory_used_mb' => 10.5,
            'peak_memory_mb' => 15.2,
            'query_count' => 8,
        ];

        $resource = new TaskReportResource($reportData);
        $response = $resource->toArray(request());

        $this->assertIsArray($response);
        $this->assertArrayHasKey('report', $response);
        $this->assertArrayHasKey('statistics', $response);
        $this->assertArrayHasKey('filters', $response);
        $this->assertArrayHasKey('meta', $response);
    }

    /**
     * Test: Meta fields are included correctly
     */
    public function test_meta_fields_included_correctly(): void
    {
        $reportData = [
            'report' => [],
            'total_tasks' => 0,
            'date_range' => ['start' => '2025-01-01', 'end' => '2025-01-31'],
            'user_filter' => null,
            'generated_at' => '2025-01-31T12:00:00+00:00',
            'cached' => true,
            'execution_time_ms' => 10.5,
            'memory_used_mb' => 5.2,
            'peak_memory_mb' => 8.1,
            'query_count' => 8,
        ];

        $resource = new TaskReportResource($reportData);
        $response = $resource->toArray(request());

        $this->assertArrayHasKey('meta', $response);
        $this->assertEquals('2025-01-31T12:00:00+00:00', $response['meta']['generated_at']);
        $this->assertTrue($response['meta']['cached']);
        $this->assertArrayHasKey('performance', $response['meta']);
        $this->assertEquals(10.5, $response['meta']['performance']['execution_time_ms']);
        $this->assertEquals(5.2, $response['meta']['performance']['memory_used_mb']);
        $this->assertEquals(8.1, $response['meta']['performance']['peak_memory_mb']);
        $this->assertEquals(8, $response['meta']['performance']['query_count']);
    }

    /**
     * Test: Statistics section formatted correctly
     */
    public function test_statistics_section_formatted_correctly(): void
    {
        $reportData = [
            'report' => [],
            'total_tasks' => 42,
            'date_range' => ['start' => '2025-01-01', 'end' => '2025-01-31'],
            'user_filter' => null,
            'generated_at' => '2025-01-31T12:00:00+00:00',
            'cached' => false,
            'execution_time_ms' => 10.5,
            'memory_used_mb' => 5.2,
            'peak_memory_mb' => 8.1,
            'query_count' => 8,
        ];

        $resource = new TaskReportResource($reportData);
        $response = $resource->toArray(request());

        $this->assertArrayHasKey('statistics', $response);
        $this->assertEquals(42, $response['statistics']['total_tasks']);
    }

    /**
     * Test: Filters section formatted correctly
     */
    public function test_filters_section_formatted_correctly(): void
    {
        $reportData = [
            'report' => [],
            'total_tasks' => 0,
            'date_range' => ['start' => '2025-01-01', 'end' => '2025-01-31'],
            'user_filter' => 'John Doe',
            'generated_at' => '2025-01-31T12:00:00+00:00',
            'cached' => false,
            'execution_time_ms' => 10.5,
            'memory_used_mb' => 5.2,
            'peak_memory_mb' => 8.1,
            'query_count' => 8,
        ];

        $resource = new TaskReportResource($reportData);
        $response = $resource->toArray(request());

        $this->assertArrayHasKey('filters', $response);
        $this->assertArrayHasKey('date_range', $response['filters']);
        $this->assertEquals('2025-01-01', $response['filters']['date_range']['start']);
        $this->assertEquals('2025-01-31', $response['filters']['date_range']['end']);
        $this->assertEquals('John Doe', $response['filters']['user_filter']);
    }
}
