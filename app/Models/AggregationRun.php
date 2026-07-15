<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AggregationRun extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'aggregation_runs';
    protected $guarded = [];
    protected $casts = [
        'cleanup_enabled' => 'boolean',
        'from_utc' => 'datetime',
        'to_utc' => 'datetime',
        'cursor_timestamp' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $run) {
            $run->id ??= (string) Str::uuid();
        });
    }
}
