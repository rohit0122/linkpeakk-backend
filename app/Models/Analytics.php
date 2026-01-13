<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'bio_page_id',
        'link_id',
        'type',
        'date',
        'count',
        'unique_count',
    ];

    protected $casts = [
        'date' => 'date',
        'count' => 'integer',
        'unique_count' => 'integer',
    ];

    public function bioPage()
    {
        return $this->belongsTo(BioPage::class);
    }

    public function link()
    {
        return $this->belongsTo(Link::class);
    }
}
