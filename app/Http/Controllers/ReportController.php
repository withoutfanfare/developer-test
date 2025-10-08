<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskReportRequest;
use App\Services\TaskReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ReportController extends Controller
{
    public function __construct(
        protected TaskReportService $reportService
    ) {}

    public function taskReport(TaskReportRequest $request)
    {
        $validated = $request->validated();

        $startDate = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])
            : now()->subMonth();

        $endDate = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])
            : now();

        $userFilter = $validated['user_filter'] ?? null;

        // Log incoming request
        \Log::info('Task report requested', [
            'user_id' => $request->user()?->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'user_filter' => $userFilter,
            'ip' => $request->ip(),
        ]);

        // Generate cache key from parameters
        $cacheKey = "task_report:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}:{$userFilter}";

        try {
            // Wrap report generation in cache (1 hour TTL)
            $reportData = Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate, $userFilter) {
                return $this->reportService->generateReport($startDate, $endDate, $userFilter);
            });

            // Mark if response came from cache
            $reportData['cached'] = Cache::has($cacheKey);

            // Log successful generation
            \Log::info('Task report generated successfully', [
                'user_id' => $request->user()?->id,
                'task_count' => $reportData['total_tasks'],
                'cached' => $reportData['cached'],
                'execution_time_ms' => $reportData['execution_time_ms'],
            ]);

            return response()->json($reportData);
        } catch (\Exception $e) {
            // Log errors with full context
            \Log::error('Task report generation failed', [
                'user_id' => $request->user()?->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
