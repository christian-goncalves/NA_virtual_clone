<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetricPageView extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'occurred_at',
        'route',
        'event_type',
        'category',
        'session_hash',
        'ip_hash',
        'user_agent',
        'context',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'context' => 'array',
        ];
    }
}
