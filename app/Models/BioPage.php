<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\FormattedResponseTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BioPage extends Model
{
    use HasFactory, FormattedResponseTrait;
    
    protected static function booted()
    {
        static::saved(fn ($page) => $page->purgeCache());
        static::deleted(fn ($page) => $page->purgeCache());
    }

    protected $fillable = [
        'user_id',
        'slug',
        'title',
        'bio',
        'template',
        'theme',
        'profile_image',
        'profile_image_blob',
        'seo',
        'social_links',
        'views',
        'unique_views',
        'likes',
        'branding',
        'is_active',
        'is_sensitive',
        'show_branding',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'profile_image_blob',
        'views',
    ];

    protected $appends = [
        'total_views',
    ];

    protected $casts = [
        'seo' => 'array',
        'social_links' => 'array',
        'branding' => 'array',
        'is_active' => 'boolean',
        'is_sensitive' => 'boolean',
        'show_branding' => 'boolean',
        'views' => 'integer',
        'unique_views' => 'integer',
        'likes' => 'integer',
    ];

    protected function totalViews(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->views,
        );
    }

    /**
     * Get absolute URL for the profile image.
     */
    protected function profileImage(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->profile_image_blob 
                ? 'data:image/webp;base64,' . base64_encode($this->profile_image_blob) 
                : null,
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function links()
    {
        return $this->hasMany(Link::class);
    }

    public function analytics()
    {
        return $this->hasMany(Analytics::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Purge cache for this bio page.
     */
    public function purgeCache()
    {
        cache()->forget("public_page_{$this->slug}");
    }
}
