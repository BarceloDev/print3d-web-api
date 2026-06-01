<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'plan_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'plan_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
