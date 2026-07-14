<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'company_id', 'name', 'department', 'country', 'timezone', 'status',
        'client_key', 'current_app', 'current_title',
        'current_domain', 'last_active', 'active_time_today', 'idle_time_today',
    ];

    protected $casts = [
        'last_active' => 'datetime',
        'active_time_today' => 'integer',
        'idle_time_today' => 'integer',
    ];

    public function devices() { return $this->hasMany(Device::class, 'employee_id', 'id'); }
    public function events() { return $this->hasMany(ActivityEvent::class, 'employee_id', 'id'); }
}
