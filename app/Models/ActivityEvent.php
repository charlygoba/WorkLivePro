<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityEvent extends Model
{
    protected $table = 'activity_events';
    public $timestamps = false;
    protected $fillable = ['id', 'company_id', 'employee_id', 'employee_name', 'department', 'event_timestamp', 'event_type', 'app', 'title', 'domain', 'duration', 'agent_id'];
    protected $casts = ['event_timestamp' => 'datetime', 'duration' => 'integer'];
}
