<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'fee_master_id',
        'fee_head',
        'fee_type',
        'amount',
        'fee_month',
        'fee_year',
        'status',
        'date',
        'time',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
