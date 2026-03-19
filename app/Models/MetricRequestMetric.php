<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricRequestMetric extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'occurred_at',
        'route',
        'http_method',
        'status_code',
        'duration_ms',
        'session_hash',
        'ip_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
        ];
    }
}
