<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'registration_number',
        'jurisdiction',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'tax_id',
        'lei',
        'verification_status',
        'verification_level',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifications()
    {
        return $this->hasMany(BusinessVerification::class);
    }
}
