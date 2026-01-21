<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractReview extends Model
{
    protected $fillable = [
        'contract_id',
        'reviewer_id',
        'reviewee_id',
        'rating',
        'comment',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
