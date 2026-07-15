<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity5mBucket extends Model
{
    protected $table = 'activity_5m_buckets';
    protected $guarded = [];
    protected $casts = [
        'bucket_start_utc' => 'datetime',
        'bucket_end_utc' => 'datetime',
        'first_event_at' => 'datetime',
        'last_event_at' => 'datetime',
    ];
}
