<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrustSettingLog extends Model
{
    protected $table = 'trust_settings_logs';
    protected $fillable = [
        'admin_id',
        'before',
        'after',
    ];
    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];
}
