<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\FormattedResponseTrait;

class Lead extends Model
{
    use HasFactory, FormattedResponseTrait;

    protected $fillable = [
        'bio_page_id',
        'name',
        'email',
        'message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function bioPage()
    {
        return $this->belongsTo(BioPage::class);
    }
}
