<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskReportValidationTest extends TestCase
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
     * Test: Invalid start_date format returns 422 with descriptive message
     */
    public function test_invalid_start_date_format_returns_422(): void
    {
        $response = $this->getJson('/api/v1/reports/tasks?start_date=invalid-date');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('start_date');
    }

    /**
     * Test: Invalid end_date format returns 422
     */
    public function test_invalid_end_date_format_returns_422(): void
    {
        $response = $this->getJson('/api/v1/reports/tasks?end_date=not-a-date');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('end_date');
    }

    /**
     * Test: end_date before start_date returns 422
     */
    public function test_end_date_before_start_date_returns_422(): void
    {
        $response = $this->getJson('/api/v1/reports/tasks?start_date=2025-12-31&end_date=2025-01-01');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('end_date');
    }

    /**
     * Test: user_filter > 255 chars returns 422
     */
    public function test_user_filter_exceeding_max_length_returns_422(): void
    {
        $longFilter = str_repeat('a', 256);

        $response = $this->getJson('/api/v1/reports/tasks?user_filter=' . $longFilter);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('user_filter');
    }

    /**
     * Test: All valid parameters pass validation (200)
     */
    public function test_valid_parameters_pass_validation(): void
    {
        $response = $this->getJson('/api/v1/reports/tasks?start_date=2025-01-01&end_date=2025-12-31&user_filter=John');

        $response->assertStatus(200);
    }

    /**
     * Test: Missing optional parameters use defaults
     */
    public function test_missing_optional_parameters_use_defaults(): void
    {
        $response = $this->getJson('/api/v1/reports/tasks');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'report',
            'total_tasks',
            'date_range' => ['start', 'end'],
        ]);
    }
}
