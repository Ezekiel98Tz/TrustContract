<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'document_type',
        'document_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'notes',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
