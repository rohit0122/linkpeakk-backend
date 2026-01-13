<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'from_plan_id',
        'to_plan_id',
        'scheduled_for',
        'status',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function fromPlan()
    {
        return $this->belongsTo(Plan::class, 'from_plan_id');
    }

    public function toPlan()
    {
        return $this->belongsTo(Plan::class, 'to_plan_id');
    }
}
