<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $table = 'agent_tokens';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'company_id', 'employee_id', 'token_hash', 'last_seen_at', 'device_id'];
    protected $hidden = ['token_hash'];
    protected $casts = ['last_seen_at' => 'datetime'];
}
