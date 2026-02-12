<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisputeMessage extends Model
{
    protected $fillable = [
        'dispute_id',
        'user_id',
        'body',
    ];

    public function dispute()
    {
        return $this->belongsTo(Dispute::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
