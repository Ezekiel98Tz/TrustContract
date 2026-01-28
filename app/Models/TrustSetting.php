<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrustSetting extends Model
{
    protected $table = 'trust_settings';

    protected $fillable = [
        'min_for_contract',
        'min_for_high_value',
        'currency_thresholds',
    ];

    protected $casts = [
        'currency_thresholds' => 'array',
    ];
}
