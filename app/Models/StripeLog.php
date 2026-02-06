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
        'subscription_id',
        'payment_intent_id',
        'amount',
        'currency',
        'status',
        'product_name',
        'next_payment_date',
        'next_payment_status',
        'interval',
        'interval_count',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'next_payment_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
