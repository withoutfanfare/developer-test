<?php

namespace Tests\Feature;

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
}
