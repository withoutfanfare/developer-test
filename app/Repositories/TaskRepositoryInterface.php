<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface TaskRepositoryInterface
{
    /**
     * Get tasks within a date range with optional user filter
     */
    public function getTasksInDateRange(Carbon $start, Carbon $end, ?string $userFilter): Collection;

    /**
     * Get category statistics for the given date range
     */
    public function getCategoryStatistics(Carbon $start, Carbon $end): array;

    /**
     * Get user statistics for the given date range
     */
    public function getUserStatistics(Carbon $start, Carbon $end): array;

    /**
     * Get status distribution for the given date range
     */
    public function getStatusDistribution(Carbon $start, Carbon $end): array;

    /**
     * Get priority distribution for the given date range
     */
    public function getPriorityDistribution(Carbon $start, Carbon $end): array;
}
