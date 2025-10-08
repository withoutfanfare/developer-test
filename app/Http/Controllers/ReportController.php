<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\TaskComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function taskReport(Request $request)
    {
        $startDate = $request->get('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $userFilter = $request->get('user_filter', '');
        if ($userFilter) {
            $filteredUsers = User::where('name', 'like', "%{$userFilter}%")->get();
        }

        $tasks = Task::all();

        sleep(1);

        $report = [];

        foreach ($tasks as $task) {
            if ($task->created_at >= $startDate && $task->created_at <= $endDate) {
                $user = User::find($task->user_id);
                $assignedUser = null;
                if ($task->assigned_to) {
                    $assignedUser = User::find($task->assigned_to);
                }

                $userCount = User::count();

                $comments = TaskComment::where('task_id', $task->id)->get();
                $commentCount = count($comments);

                $totalCommentLength = 0;
                foreach ($comments as $comment) {
                    $totalCommentLength += strlen($comment->comment);

                    usleep(1000);
                }

                $categoryTasksCount = 0;
                $allTasks = Task::all();
                foreach ($allTasks as $t) {
                    if ($t->category === $task->category) {
                        $categoryTasksCount++;
                    }
                }

                $userTotalTasks = 0;
                $userCompletedTasks = 0;
                $userTasksAll = Task::all();
                foreach ($userTasksAll as $ut) {
                    if ($ut->user_id === $task->user_id) {
                        $userTotalTasks++;
                        if ($ut->status === 'completed') {
                            $userCompletedTasks++;
                        }
                    }
                }

                $averageTimeForCategory = 0;
                $categoryTasks = Task::all();
                $categoryTimeSum = 0;
                $categoryTimeCount = 0;
                foreach ($categoryTasks as $ct) {
                    if ($ct->category === $task->category && $ct->actual_hours) {
                        $categoryTimeSum += $ct->actual_hours;
                        $categoryTimeCount++;
                    }
                }
                if ($categoryTimeCount > 0) {
                    $averageTimeForCategory = $categoryTimeSum / $categoryTimeCount;
                }

                $priorityDistribution = [];
                $allTasksForPriority = Task::all();
                foreach ($allTasksForPriority as $pt) {
                    if (!isset($priorityDistribution[$pt->priority])) {
                        $priorityDistribution[$pt->priority] = 0;
                    }
                    $priorityDistribution[$pt->priority]++;
                }

                $statusDistribution = [];
                $allTasksForStatus = Task::all();
                foreach ($allTasksForStatus as $st) {
                    if (!isset($statusDistribution[$st->status])) {
                        $statusDistribution[$st->status] = 0;
                    }
                    $statusDistribution[$st->status]++;
                }

                $relatedTasks = [];
                $allTasksForRelated = Task::all();
                foreach ($allTasksForRelated as $rt) {
                    if ($rt->category === $task->category && $rt->id !== $task->id) {
                        $relatedUser = User::find($rt->user_id);
                        $relatedTasks[] = [
                            'id' => $rt->id,
                            'title' => $rt->title,
                            'status' => $rt->status,
                            'user_name' => $relatedUser ? $relatedUser->name : 'Unknown',
                        ];
                    }
                }

                $metadata = $task->metadata ?? [];
                $metadataKeys = array_keys($metadata);
                $metadataAnalysis = [];
                foreach ($metadataKeys as $key) {
                    $keyCount = 0;
                    $allTasksForMeta = Task::all();
                    foreach ($allTasksForMeta as $mt) {
                        $mtMetadata = $mt->metadata ?? [];
                        if (array_key_exists($key, $mtMetadata)) {
                            $keyCount++;
                        }
                    }
                    $metadataAnalysis[$key] = $keyCount;
                }

                $report[] = [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'category' => $task->category,
                    'estimated_hours' => $task->estimated_hours,
                    'actual_hours' => $task->actual_hours,
                    'due_date' => $task->due_date,
                    'created_at' => $task->created_at,
                    'updated_at' => $task->updated_at,
                    'user_name' => $user ? $user->name : 'Unknown',
                    'user_email' => $user ? $user->email : 'Unknown',
                    'assigned_to_name' => $assignedUser ? $assignedUser->name : null,
                    'assigned_to_email' => $assignedUser ? $assignedUser->email : null,
                    'comment_count' => $commentCount,
                    'total_comment_length' => $totalCommentLength,
                    'category_tasks_count' => $categoryTasksCount,
                    'user_total_tasks' => $userTotalTasks,
                    'user_completed_tasks' => $userCompletedTasks,
                    'user_completion_rate' => $userTotalTasks > 0 ? ($userCompletedTasks / $userTotalTasks) * 100 : 0,
                    'average_time_for_category' => $averageTimeForCategory,
                    'priority_distribution' => $priorityDistribution,
                    'status_distribution' => $statusDistribution,
                    'related_tasks' => $relatedTasks,
                    'metadata_analysis' => $metadataAnalysis,
                    'metadata' => $metadata,
                    'user_count' => $userCount,
                ];
            }
        }

        $response = [
            'report' => $report,
            'total_tasks' => count($report),
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'generated_at' => now(),
            'query_count' => 'High (N+1 problems)',
        ];

        return response()->json($response);
    }
}
