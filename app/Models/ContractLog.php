<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}