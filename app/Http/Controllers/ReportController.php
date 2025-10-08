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

        // Generate cache key from parameters
        $cacheKey = "task_report:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}:{$userFilter}";

        // Wrap report generation in cache
        $reportData = Cache::tags(['task_reports'])->remember($cacheKey, 3600, function () use ($startDate, $endDate, $userFilter) {
            return $this->reportService->generateReport($startDate, $endDate, $userFilter);
        });

        // Mark if response came from cache
        $reportData['cached'] = Cache::tags(['task_reports'])->has($cacheKey);

        return response()->json($reportData);
    }
}
