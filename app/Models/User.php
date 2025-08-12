<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
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

    public static function getIdByCode($code): ?int
    {
        return self::query()->where('code', $code)->select('id')->first()?->id;
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

    public function incomeMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function outcomeMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(static function (User $user) {
            $user->incomeMessages()->delete();
            $user->outcomeMessages()->delete();
        });
    }
}
