<?php

namespace App\Repositories;

use App\Enums\TaskStatus;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Get tasks within a date range with optional user filter
     */
    public function getTasksInDateRange(Carbon $start, Carbon $end, ?string $userFilter): Collection
    {
        $query = Task::with(['user', 'assignedTo', 'comments'])
            ->inDateRange($start, $end);

        if ($userFilter) {
            $query->whereHas('user', function ($q) use ($userFilter) {
                $q->where('name', 'like', "%{$userFilter}%");
            });
        }

        return $query->get();
    }

    /**
     * Get category statistics for the given date range
     */
    public function getCategoryStatistics(Carbon $start, Carbon $end): array
    {
        $stats = Task::select('category')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('AVG(actual_hours) as avg_hours')
            ->inDateRange($start, $end)
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
     * Get user statistics for the given date range
     */
    public function getUserStatistics(Carbon $start, Carbon $end): array
    {
        $stats = Task::select('user_id')
            ->selectRaw('COUNT(*) as total_tasks')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_tasks', [TaskStatus::COMPLETED->value])
            ->whereBetween('created_at', [$start, $end])
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
     * Get status distribution for the given date range
     */
    public function getStatusDistribution(Carbon $start, Carbon $end): array
    {
        return Task::select('status')
            ->selectRaw('COUNT(*) as count')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();
    }

    /**
     * Get priority distribution for the given date range
     */
    public function getPriorityDistribution(Carbon $start, Carbon $end): array
    {
        return Task::select('priority')
            ->selectRaw('COUNT(*) as count')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->all();
    }
}
