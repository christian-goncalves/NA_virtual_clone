<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualMeeting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'external_id',
        'name',
        'meeting_platform',
        'meeting_url',
        'meeting_id',
        'meeting_password',
        'phone',
        'region',
        'state',
        'city',
        'neighborhood',
        'format_labels',
        'type_label',
        'interest_labels',
        'weekday',
        'start_time',
        'end_time',
        'duration_minutes',
        'timezone',
        'is_open',
        'is_study',
        'is_lgbt',
        'is_women',
        'is_hybrid',
        'source_url',
        'source_hash',
        'is_active',
        'last_seen_at',
        'synced_at',
        'auto_join_enabled',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'format_labels' => 'array',
            'interest_labels' => 'array',
            'is_open' => 'boolean',
            'is_study' => 'boolean',
            'is_lgbt' => 'boolean',
            'is_women' => 'boolean',
            'is_hybrid' => 'boolean',
            'is_active' => 'boolean',
            'auto_join_enabled' => 'boolean',
            'last_seen_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
