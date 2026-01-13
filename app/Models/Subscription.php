<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\FormattedResponseTrait;

class Subscription extends Model
{
    use HasFactory, FormattedResponseTrait;

    protected $fillable = [
        'user_id',
        'plan_id',
        'razorpay_subscription_id',
        'razorpay_customer_id',
        'status',
        'trial_ends_at',
        'ends_at',
        'cancelled_at',
        'current_period_start',
        'current_period_end',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function planChanges()
    {
        return $this->hasMany(PlanChange::class);
    }

    public function isPaid()
    {
        return $this->plan && $this->plan->price > 0;
    }

    public function isActive()
    {
        return in_array($this->status, ['active', 'trialing']);
    }
}
