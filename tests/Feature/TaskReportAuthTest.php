<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskReportAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Unauthenticated request returns 401
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/reports/tasks');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /**
     * Test: Invalid token returns 401
     */
    public function test_invalid_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/reports/tasks', [
            'Authorization' => 'Bearer invalid-token-12345',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test: Valid token allows access (200)
     */
    public function test_valid_token_allows_access(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/reports/tasks');

        $response->assertStatus(200);
    }

    /**
     * Test: 60 requests succeed, 61st returns 429
     */
    public function test_rate_limiting_blocks_after_60_requests(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Make 60 requests - all should succeed
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/v1/reports/tasks');
            $response->assertSuccessful();
        }

        // 61st request should be rate limited
        $response = $this->getJson('/api/v1/reports/tasks');
        $response->assertStatus(429);
    }

    /**
     * Test: Rate limit header present in responses
     */
    public function test_rate_limit_headers_present(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/reports/tasks');

        $response->assertSuccessful();
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }
}
