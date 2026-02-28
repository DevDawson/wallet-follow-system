<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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

    protected static function booted()
{
    static::created(function ($user) {
        $user->wallet()->create(['balance' => 0]);
    });
}

    // Uhusiano na Wallet
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    // Transactions alizotuma (kama sender)
    public function sentTransactions()
    {
        return $this->hasManyThrough(
            Transaction::class,
            Wallet::class,
            'user_id', // foreign key on wallets table
            'sender_wallet_id', // foreign key on transactions table
            'id', // local key on users table
            'id' // local key on wallets table
        );
    }

    // Transactions alizopokea (kama receiver)
    public function receivedTransactions()
    {
        return $this->hasManyThrough(
            Transaction::class,
            Wallet::class,
            'user_id',
            'receiver_wallet_id',
            'id',
            'id'
        );
    }

    // Watumiaji anaowafuata (following)
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
                    ->withTimestamps();
    }

    // Watumiaji wanaomfuata (followers)
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
                    ->withTimestamps();
    }

    // Kurahisisha: angalia kama anamfuata mtumiaji fulani
    public function isFollowing(User $user)
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

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
}
