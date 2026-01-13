<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\FormattedResponseTrait;

use Illuminate\Database\Eloquent\Casts\Attribute;

class Link extends Model
{
    use HasFactory, FormattedResponseTrait;
    
    protected $touches = ['bioPage'];

    protected $fillable = [
        'user_id',
        'bio_page_id',
        'title',
        'url',
        'icon',
        'is_active',
        'order',
        'clicks',
        'unique_clicks',
    ];

    protected $hidden = [
        'clicks',
    ];

    protected $appends = [
        'total_clicks',
    ];

    protected function totalClicks(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->clicks,
        );
    }
    
    protected $casts = [
        'clicks' => 'integer',
        'unique_clicks' => 'integer',
        'is_active' => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bioPage()
    {
        return $this->belongsTo(BioPage::class);
    }
}
