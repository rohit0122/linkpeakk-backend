<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\FormattedResponseTrait;

class NewsletterSubscriber extends Model
{
    use HasFactory, FormattedResponseTrait;

    protected $fillable = [
        'email',
    ];
}
