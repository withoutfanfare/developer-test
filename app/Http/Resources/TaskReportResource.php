<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'report' => $this->resource['report'],
            'statistics' => [
                'total_tasks' => $this->resource['total_tasks'],
            ],
            'filters' => [
                'date_range' => $this->resource['date_range'],
                'user_filter' => $this->resource['user_filter'],
            ],
            'meta' => [
                'generated_at' => $this->resource['generated_at'],
                'cached' => $this->resource['cached'],
                'performance' => [
                    'execution_time_ms' => $this->resource['execution_time_ms'],
                    'memory_used_mb' => $this->resource['memory_used_mb'] ?? null,
                    'peak_memory_mb' => $this->resource['peak_memory_mb'] ?? null,
                    'query_count' => $this->resource['query_count'],
                ],
            ],
        ];
    }
}
