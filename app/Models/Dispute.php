<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'contract_id',
        'initiator_id',
        'reason',
        'status',
        'resolved_at',
        'provider',
        'external_event_id',
        'resolution',
        'mediator_id',
        'mediation_notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function mediator()
    {
        return $this->belongsTo(User::class, 'mediator_id');
    }
}
