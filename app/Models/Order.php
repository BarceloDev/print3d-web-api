<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'title',
        'description',
        'price',
        'deadline',
        'status',
        'public_token',
        'reference_image',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'deadline' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->public_token = (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
