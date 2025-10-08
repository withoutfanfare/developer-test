<?php

namespace App\Services;

use App\Repositories\TaskRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaskReportService
{
    public function __construct(
        protected TaskRepositoryInterface $repository
    ) {}

    /**
     * Generate a comprehensive task report
     */
    public function generateReport(Carbon $startDate, Carbon $endDate, ?string $userFilter): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Enable query logging to track database queries
        \DB::enableQueryLog();

        // Get data from repository
        $tasks = $this->repository->getTasksInDateRange($startDate, $endDate, $userFilter);
        $categoryStats = $this->repository->getCategoryStatistics($startDate, $endDate);
        $userStats = $this->repository->getUserStatistics($startDate, $endDate);
        $priorityDistribution = $this->repository->getPriorityDistribution($startDate, $endDate);
        $statusDistribution = $this->repository->getStatusDistribution($startDate, $endDate);

        // Get query count
        $queryCount = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        // Format tasks for response
        $report = $this->formatTasksForResponse(
            $tasks,
            $categoryStats,
            $userStats,
            $priorityDistribution,
            $statusDistribution
        );

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        $endMemory = memory_get_usage(true);
        $memoryUsed = round(($endMemory - $startMemory) / 1024 / 1024, 2); // Convert to MB
        $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2); // Peak memory in MB

        // Log slow queries (>1s) as warnings
        if ($executionTime > 1000) {
            \Log::warning('Slow report generation detected', [
                'execution_time_ms' => $executionTime,
                'memory_used_mb' => $memoryUsed,
                'task_count' => count($report),
                'query_count' => $queryCount,
                'date_range' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            ]);
        }

        // Warn if query count exceeds threshold
        if ($queryCount > 100) {
            \Log::warning('High query count detected', [
                'query_count' => $queryCount,
                'task_count' => count($report),
                'execution_time_ms' => $executionTime,
                'date_range' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            ]);
        }

        return [
            'report' => $report,
            'total_tasks' => count($report),
            'category_stats' => $categoryStats,
            'user_stats' => $userStats,
            'priority_distribution' => $priorityDistribution,
            'status_distribution' => $statusDistribution,
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'user_filter' => $userFilter ?: null,
            'generated_at' => now()->toIso8601String(),
            'cached' => false,
            'execution_time_ms' => $executionTime,
            'memory_used_mb' => $memoryUsed,
            'peak_memory_mb' => $peakMemory,
            'query_count' => $queryCount,
        ];
    }

    /**
     * Format tasks collection for API response
     */
    private function formatTasksForResponse(
        Collection $tasks,
        array $categoryStats,
        array $userStats,
        array $priorityDistribution,
        array $statusDistribution
    ): array {
        return $tasks->map(function ($task) use ($categoryStats, $userStats) {
            $userId = $task->user_id;
            $category = $task->category;

            return [
                'task_id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'category' => $category,
                'estimated_hours' => $task->estimated_hours,
                'actual_hours' => $task->actual_hours,
                'due_date' => $task->due_date,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
                'user_name' => $task->user->name ?? 'Unknown',
                'user_email' => $task->user->email ?? 'Unknown',
                'assigned_to_name' => $task->assignedTo->name ?? null,
                'assigned_to_email' => $task->assignedTo->email ?? null,
                'comment_count' => $task->comments->count(),
                'total_comment_length' => $task->comments->sum(function ($comment) {
                    return strlen($comment->comment ?? '');
                }),
                // Handle null categories gracefully (use empty string as key for null categories)
                'category_tasks_count' => isset($category) ? ($categoryStats[$category]['count'] ?? 0) : 0,
                'user_total_tasks' => $userStats[$userId]['total_tasks'] ?? 0,
                'user_completed_tasks' => $userStats[$userId]['completed_tasks'] ?? 0,
                'user_completion_rate' => $userStats[$userId]['completion_rate'] ?? 0,
                'average_time_for_category' => isset($category) ? ($categoryStats[$category]['avg_hours'] ?? 0) : 0,
                'metadata' => $task->metadata ?? [],
            ];
        })->values()->all();
    }
}
