<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\FormattedResponseTrait;

class Ticket extends Model
{
    use HasFactory, FormattedResponseTrait;
    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'status',
        'priority',
        'category',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
