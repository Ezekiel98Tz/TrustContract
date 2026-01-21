<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractSignature extends Model
{
    use HasFactory;

    protected $hidden = [
        'ip_address',
        'device_info',
        'fingerprint_hash',
    ];

    protected $fillable = [
        'contract_id',
        'user_id',
        'signed_at',
        'ip_address',
        'device_info',
        'fingerprint_hash',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
