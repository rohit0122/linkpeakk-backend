<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Traits\FormattedResponseTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, FormattedResponseTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'avatar_url',
        'avatar_url_blob',
        'verification_token',
        'role',
        'is_active',
        'suspended_at',
        'suspension_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
        'avatar_url_blob',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'suspended_at' => 'datetime',
        'suspension_reason' => 'string',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function bioPages()
    {
        return $this->hasMany(BioPage::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->whereIn('status', ['active', 'trialing', 'pending'])->latestOfMany();
    }

    public function links()
    {
        return $this->hasMany(Link::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Check if user can access a feature or is within limits.
     *
     * @param string $feature Key in the features JSON (e.g., 'links')
     * @param int|null $currentUsage Current usage count to check against limit
     * @return bool
     */
    public function canAccessFeature(string $feature, $value = null): bool
    {
        $subscription = $this->activeSubscription()->with('plan')->first();
        
        if (!$subscription || !$subscription->plan) {
            $plan = \App\Models\Plan::where('slug', 'free')->first();
        } else {
            $plan = $subscription->plan;
        }

        if (!$plan) {
            return false;
        }

        if (empty($plan->features)) {
            return false;
        }

        $limit = $plan->features[$feature] ?? null;

        if ($limit === null) {
            return false; 
        }

        if ($limit === 'ALL') {
            return true;
        }

        // Handle numeric limits (usage checks)
        if (is_numeric($value) && is_numeric($limit)) {
            return $value < $limit;
        }

        // Handle list-based features (e.g., allowedTemplates, themes)
        if (is_array($limit) && $value !== null) {
            return in_array($value, $limit);
        }

        // Handle boolean features
        return (bool) $limit;
    }

    /**
     * Get absolute URL for the avatar.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->avatar_url_blob 
                ? 'data:image/webp;base64,' . base64_encode($this->avatar_url_blob) 
                : null,
        );
    }
}
