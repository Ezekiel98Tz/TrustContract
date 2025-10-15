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
