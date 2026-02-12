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

    public function completion(): array
    {
        $fields = [
            'company_name' => !!$this->company_name,
            'registration_number' => !!$this->registration_number,
            'jurisdiction' => !!$this->jurisdiction,
            'address_line1' => !!$this->address_line1,
            'city' => !!$this->city,
            'postal_code' => !!$this->postal_code,
            'verification' => ($this->verification_status === 'verified'),
        ];
        $total = count($fields);
        $completed = array_sum(array_map(fn($v) => $v ? 1 : 0, $fields));
        $percent = (int) round(($completed / max(1, $total)) * 100);
        $missing = array_keys(array_filter($fields, fn($v) => !$v));
        return ['percent' => $percent, 'missing' => $missing];
    }
}
