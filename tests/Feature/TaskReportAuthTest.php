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
     * Test: Rate limiting is configured (basic verification)
     */
    public function test_rate_limiting_is_configured(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Verify the endpoint has throttle middleware
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        $route = $routes->getByName(null);

        foreach ($routes as $route) {
            if ($route->uri() === 'api/v1/reports/tasks') {
                $middlewares = $route->middleware();
                $this->assertContains('throttle:60,1', $middlewares, 'Route should have throttle middleware');
                break;
            }
        }

        // Verify at least one request works
        $response = $this->getJson('/api/v1/reports/tasks');
        $response->assertSuccessful();
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
