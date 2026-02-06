<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'stripe_customer_id',
        'session_id',
        'payment_intent_id',
        'amount',
        'currency',
        'status',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
