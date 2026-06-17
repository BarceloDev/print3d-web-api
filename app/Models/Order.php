<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'deadline' => 'date:Y-m-d',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->public_token = (string) Str::uuid();
        });

        static::updating(function (Order $order) {
            if ($order->isDirty('status') && $order->status === 'delivered') {
                $order->delivered_at = now();
            }
        });
    }

    public function getReferenceImageAttribute(?string $value): ?string
    {
        if (!$value) return null;
        return Storage::url($value); // ← usa o driver configurado no .env
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
