<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormattedResponseTrait;

class Plan extends Model
{
    use HasFactory, FormattedResponseTrait;

    protected $fillable = [
        'name',
        'slug',
        'razorpay_plan_id',
        'price',
        'currency',
        'billing_interval',
        'trial_days',
        'is_active',
        'features',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
