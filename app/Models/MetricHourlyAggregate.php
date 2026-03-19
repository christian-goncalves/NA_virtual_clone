<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricHourlyAggregate extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'hour_bucket',
        'metric_key',
        'dimension',
        'total_count',
        'avg_duration_ms',
        'p95_duration_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hour_bucket' => 'datetime',
        ];
    }
}
