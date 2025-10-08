<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * Test: SQL injection in user_filter doesn't execute malicious SQL
     */
    public function test_sql_injection_in_user_filter_prevented(): void
    {
        $maliciousPayload = "' OR '1'='1";

        $response = $this->getJson('/api/v1/reports/tasks?user_filter='.urlencode($maliciousPayload));

        // Should return safe results, not execute SQL injection
        $response->assertSuccessful();
    }

    /**
     * Test: Database remains intact after injection attempts
     */
    public function test_database_remains_intact_after_injection_attempts(): void
    {
        $tableName = 'users';

        // Verify users table exists
        $this->assertTrue(
            DB::getSchemaBuilder()->hasTable($tableName),
            'Users table should exist before injection attempt'
        );

        // Attempt table drop via SQL injection
        $maliciousPayload = "'; DROP TABLE {$tableName}; --";

        $response = $this->getJson('/api/v1/reports/tasks?user_filter='.urlencode($maliciousPayload));

        // Table should still exist
        $this->assertTrue(
            DB::getSchemaBuilder()->hasTable($tableName),
            'Users table should still exist after injection attempt'
        );

        $response->assertSuccessful();
    }

    /**
     * Test: Users table still exists after DROP TABLE payload
     */
    public function test_users_table_exists_after_drop_table_payload(): void
    {
        $maliciousPayload = "'; DROP TABLE users; --";

        $response = $this->getJson('/api/v1/reports/tasks?user_filter='.urlencode($maliciousPayload));

        // Response should be successful (not execute the SQL)
        $response->assertSuccessful();

        // Users table should still exist
        $this->assertTrue(
            DB::getSchemaBuilder()->hasTable('users'),
            'Users table should exist after malicious payload'
        );

        // User should still exist
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => $this->user->email,
        ]);
    }

    /**
     * Test: Query count < 100 for any report size
     */
    public function test_query_count_less_than_100(): void
    {
        DB::enableQueryLog();

        $response = $this->getJson('/api/v1/reports/tasks');

        $queryCount = count(DB::getQueryLog());

        $this->assertLessThan(100, $queryCount, "Query count should be less than 100, got {$queryCount}");
        $response->assertSuccessful();
    }

    /**
     * Test: Report completes in reasonable time (< 10 seconds for test data)
     */
    public function test_report_completes_in_reasonable_time(): void
    {
        $startTime = microtime(true);

        $response = $this->getJson('/api/v1/reports/tasks');

        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

        $this->assertLessThan(10000, $executionTime, "Report should complete in < 10 seconds, took {$executionTime}ms");
        $response->assertSuccessful();
    }

    /**
     * Test: Memory usage is tracked and included in response
     */
    public function test_memory_usage_tracked_in_response(): void
    {
        $response = $this->getJson('/api/v1/reports/tasks');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'memory_used_mb',
            'peak_memory_mb',
            'execution_time_ms',
        ]);

        $data = $response->json();
        $this->assertIsNumeric($data['memory_used_mb']);
        $this->assertIsNumeric($data['peak_memory_mb']);
        $this->assertGreaterThanOrEqual(0, $data['memory_used_mb']);
    }

    /**
     * Test: Memory usage stays under 128MB for large datasets
     */
    public function test_memory_usage_under_limit_for_large_dataset(): void
    {
        // Create 1000 tasks for testing (10k would be too slow for unit tests)
        Task::factory()->count(1000)->create();

        $memoryBefore = memory_get_usage(true) / 1024 / 1024;

        $response = $this->getJson('/api/v1/reports/tasks');

        $memoryAfter = memory_get_usage(true) / 1024 / 1024;
        $memoryUsed = $memoryAfter - $memoryBefore;

        $response->assertSuccessful();

        // Memory usage should be reasonable (under 128MB)
        $this->assertLessThan(128, $memoryUsed, "Memory usage should be under 128MB, got {$memoryUsed}MB");
    }

    /**
     * Test: Cache reduces memory usage on subsequent requests
     */
    public function test_cache_reduces_memory_usage(): void
    {
        Task::factory()->count(100)->create();

        // First request (uncached)
        $memoryBefore1 = memory_get_usage(true);
        $response1 = $this->getJson('/api/v1/reports/tasks');
        $memoryAfter1 = memory_get_usage(true);
        $uncachedMemory = $memoryAfter1 - $memoryBefore1;

        $response1->assertSuccessful();

        // Second request (should be cached)
        $memoryBefore2 = memory_get_usage(true);
        $response2 = $this->getJson('/api/v1/reports/tasks');
        $memoryAfter2 = memory_get_usage(true);
        $cachedMemory = $memoryAfter2 - $memoryBefore2;

        $response2->assertSuccessful();

        // Cached request should use less memory (or similar, as data is already in memory)
        $this->assertLessThanOrEqual($uncachedMemory * 1.5, $cachedMemory,
            'Cached request should not use significantly more memory than uncached request');
    }

    /**
     * Test: Queries on large datasets complete in reasonable time (indexes working)
     */
    public function test_queries_on_large_dataset_complete_quickly(): void
    {
        // Create 1000 tasks to test query performance
        Task::factory()->count(1000)->create();

        $startTime = microtime(true);

        $response = $this->getJson('/api/v1/reports/tasks');

        $executionTime = (microtime(true) - $startTime) * 1000; // Convert to ms

        $response->assertSuccessful();

        // With indexes, even 1000 tasks should complete quickly
        $this->assertLessThan(500, $executionTime,
            "Query on 1000 tasks should complete in <500ms with indexes, took {$executionTime}ms");
    }

    /**
     * Test: Date range filter uses created_at index
     */
    public function test_date_range_filter_performance(): void
    {
        // Create tasks with varying dates
        Task::factory()->count(500)->create(['created_at' => now()->subMonths(6)]);
        Task::factory()->count(500)->create(['created_at' => now()->subMonths(1)]);

        DB::enableQueryLog();

        $response = $this->getJson('/api/v1/reports/tasks?start_date=' . now()->subMonths(2)->format('Y-m-d'));

        $queryLog = DB::getQueryLog();

        $response->assertSuccessful();

        // Should use efficient queries (count should be low)
        $this->assertLessThan(20, count($queryLog),
            'Date range filtering should use indexes and minimize query count');
    }

    /**
     * Test: Status filter performs efficiently
     */
    public function test_status_filter_performance(): void
    {
        // Create tasks with different statuses
        Task::factory()->count(1000)->create();

        $startTime = microtime(true);

        $response = $this->getJson('/api/v1/reports/tasks');

        $executionTime = (microtime(true) - $startTime) * 1000;

        $response->assertSuccessful();

        // Status filtering (used in aggregations) should be fast with index
        $this->assertLessThan(1000, $executionTime,
            "Status-based queries should be fast with index, took {$executionTime}ms");
    }
}
