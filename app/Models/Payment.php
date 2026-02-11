<?php

namespace App\Models;

use App\Traits\FormattedResponseTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use FormattedResponseTrait, HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'razorpay_payment_link_id',
        'razorpay_payment_id',
        'amount',
        'currency',
        'status',
        'expires_at_after_payment',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expires_at_after_payment' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
