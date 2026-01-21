<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'verification_status',
        'id_document_path',
        'phone',
        'country',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'date_of_birth',
        'verification_level',
        'two_factor_enabled',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profileCompletion(): array
    {
        $fields = [
            'email_verified_at' => !!$this->email_verified_at,
            'phone' => !!$this->phone,
            'country' => !!$this->country,
            'address_line1' => !!$this->address_line1,
            'city' => !!$this->city,
            'state' => !!$this->state,
            'postal_code' => !!$this->postal_code,
            'date_of_birth' => !!$this->date_of_birth,
        ];
        $total = count($fields);
        $completed = array_sum(array_map(fn($v) => $v ? 1 : 0, $fields));
        $percent = (int) round(($completed / max(1, $total)) * 100);
        $missing = array_keys(array_filter($fields, fn($v) => !$v));
        return ['percent' => $percent, 'missing' => $missing];
    }

    // Relationships
    public function contractsAsBuyer()
    {
        return $this->hasMany(\App\Models\Contract::class, 'buyer_id');
    }

    public function contractsAsSeller()
    {
        return $this->hasMany(\App\Models\Contract::class, 'seller_id');
    }

    public function verifications()
    {
        return $this->hasMany(\App\Models\Verification::class);
    }

    public function documents()
    {
        return $this->hasMany(\App\Models\Document::class);
    }

    public function transactionsAsPayer()
    {
        return $this->hasMany(\App\Models\Transaction::class, 'payer_id');
    }

    public function transactionsAsPayee()
    {
        return $this->hasMany(\App\Models\Transaction::class, 'payee_id');
    }
}
