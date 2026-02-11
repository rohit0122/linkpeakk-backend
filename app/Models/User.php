<?php

namespace App\Models;

use App\Traits\FormattedResponseTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use FormattedResponseTrait, HasApiTokens, HasFactory, Notifiable;

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
        'verification_token',
        'role',
        'plan_id',
        'plan_expires_at',
        'pending_plan_id',
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
        'plan_expires_at' => 'datetime',
    ];

    public function bioPages()
    {
        return $this->hasMany(BioPage::class);
    }

    public function links()
    {
        return $this->hasMany(Link::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function pendingPlan()
    {
        return $this->belongsTo(Plan::class, 'pending_plan_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if user can access a feature or is within limits.
     *
     * @param  string  $feature  Key in the features JSON (e.g., 'links')
     * @param  int|null  $currentUsage  Current usage count to check against limit
     */
    public function canAccessFeature(string $feature, $value = null): bool
    {
        // If plan is not expired, use the assigned plan
        // Otherwise, default to free plan
        $isExpired = $this->plan_expires_at && $this->plan_expires_at->isPast();
        
        if (!$isExpired && $this->plan_id) {
            $plan = $this->plan;
        } else {
            $plan = \App\Models\Plan::where('slug', 'free')->first();
        }

        if (! $plan) {
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
            return $value <= $limit;
        }

        // Handle list-based features (e.g., allowedTemplates, themes)
        if (is_array($limit) && $value !== null) {
            return in_array($value, $limit);
        }

        // Handle boolean features
        return (bool) $limit;
    }

    /**
     * Check if user is in the 7-day window before expiry.
     */
    public function isInRenewalWindow(): bool
    {
        if (!$this->plan_expires_at) {
            return true; // If no expiry (free/legacy), they can pay anytime
        }

        return $this->plan_expires_at->diffInDays(now()) <= 7 || $this->plan_expires_at->isPast();
    }

    /**
     * Get absolute URL for the avatar.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? \Illuminate\Support\Facades\Storage::disk('public')->url($value) : null,
        );
    }
}
