<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisputeLog extends Model
{
    protected $fillable = [
        'dispute_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'notes',
    ];

    public function dispute()
    {
        return $this->belongsTo(Dispute::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
