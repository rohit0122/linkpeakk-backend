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
        'price',
        'currency',
        'trial_days',
        'is_active',
        'features',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

}
