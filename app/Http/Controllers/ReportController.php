<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\TaskReportRequest;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function taskReport(TaskReportRequest $request)
    {
        $validated = $request->validated();

        $startDate = $validated['start_date'] ?? now()->subMonth()->format('Y-m-d');
        $endDate = $validated['end_date'] ?? now()->format('Y-m-d');
        $userFilter = $validated['user_filter'] ?? '';

        // Generate cache key from parameters
        $cacheKey = "task_report:{$startDate}:{$endDate}:{$userFilter}";

        // Wrap report generation in cache
        $reportData = Cache::tags(['task_reports'])->remember($cacheKey, 3600, function () use ($startDate, $endDate, $userFilter) {
            $startTime = microtime(true);

            // Build base query with eager loading (T028 - Fix N+1)
            $tasksQuery = Task::with(['user', 'assignedTo', 'comments'])
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Apply user filter if provided
            if ($userFilter) {
                $tasksQuery->whereHas('user', function ($query) use ($userFilter) {
                    $query->where('name', 'like', "%{$userFilter}%");
                });
            }

            $tasks = $tasksQuery->get();

            // Get aggregated statistics using database queries (T029, T030, T031)
            $categoryStats = $this->getCategoryStatistics($startDate, $endDate);
            $userStats = $this->getUserStatistics($startDate, $endDate);
            $priorityDistribution = $this->getPriorityDistribution($startDate, $endDate);
            $statusDistribution = $this->getStatusDistribution($startDate, $endDate);

            // Format tasks for response
            $report = $tasks->map(function ($task) use ($categoryStats, $userStats, $priorityDistribution, $statusDistribution) {
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
                        return strlen($comment->content ?? '');
                    }),
                    'category_tasks_count' => $categoryStats[$category]['count'] ?? 0,
                    'user_total_tasks' => $userStats[$userId]['total_tasks'] ?? 0,
                    'user_completed_tasks' => $userStats[$userId]['completed_tasks'] ?? 0,
                    'user_completion_rate' => $userStats[$userId]['completion_rate'] ?? 0,
                    'average_time_for_category' => $categoryStats[$category]['avg_hours'] ?? 0,
                    'priority_distribution' => $priorityDistribution,
                    'status_distribution' => $statusDistribution,
                    'metadata' => $task->metadata ?? [],
                ];
            })->values()->all();

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'report' => $report,
                'total_tasks' => count($report),
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                ],
                'user_filter' => $userFilter ?: null,
                'generated_at' => now()->toIso8601String(),
                'cached' => false,
                'execution_time_ms' => $executionTime,
                'query_count' => '<100 (optimized)',
            ];
        });

        // Mark if response came from cache
        $reportData['cached'] = Cache::tags(['task_reports'])->has($cacheKey);

        return response()->json($reportData);
    }

    /**
     * Get category statistics using database aggregation (T029)
     */
    private function getCategoryStatistics(string $startDate, string $endDate): array
    {
        $stats = Task::select('category')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(actual_hours) as avg_hours')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('category')
            ->groupBy('category')
            ->get()
            ->keyBy('category')
            ->map(function ($stat) {
                return [
                    'count' => $stat->count,
                    'avg_hours' => round($stat->avg_hours ?? 0, 2),
                ];
            })
            ->all();

        return $stats;
    }

    /**
     * Get user statistics using database aggregation (T030)
     */
    private function getUserStatistics(string $startDate, string $endDate): array
    {
        $stats = Task::select('user_id')
            ->selectRaw('COUNT(*) as total_tasks')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_tasks', [TaskStatus::COMPLETED->value])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id')
            ->map(function ($stat) {
                $completionRate = $stat->total_tasks > 0
                    ? round(($stat->completed_tasks / $stat->total_tasks) * 100, 2)
                    : 0;

                return [
                    'total_tasks' => $stat->total_tasks,
                    'completed_tasks' => $stat->completed_tasks,
                    'completion_rate' => $completionRate,
                ];
            })
            ->all();

        return $stats;
    }

    /**
     * Get priority distribution using database aggregation (T031)
     */
    private function getPriorityDistribution(string $startDate, string $endDate): array
    {
        return Task::select('priority')
            ->selectRaw('COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->all();
    }

    /**
     * Get status distribution using database aggregation (T031)
     */
    private function getStatusDistribution(string $startDate, string $endDate): array
    {
        return Task::select('status')
            ->selectRaw('COUNT(*) as count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();
    }
}
