<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'devices';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'company_id', 'employee_id', 'os', 'hostname', 'ip', 'version', 'last_sync', 'serial_number', 'ram', 'storage', 'brand', 'model', 'processor', 'disk_total_gb', 'disk_free_gb', 'disk_used_percent'];
    protected $casts = ['last_sync' => 'datetime'];
}
