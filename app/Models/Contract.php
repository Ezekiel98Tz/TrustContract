<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'title',
        'description',
        'price_cents',
        'currency',
        'deadline_at',
        'status',
        'buyer_accepted_at',
        'seller_accepted_at',
        'pdf_path',
    ];

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function signatures()
    {
        return $this->hasMany(ContractSignature::class);
    }

    public function logs()
    {
        return $this->hasMany(ContractLog::class);
    }
}