<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id', 'profile_id', 'sale_id', 'starts_at',
        'expires_at', 'status', 'cancelled_at', 'reminder_sent',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'expires_at' => 'date',
        'cancelled_at' => 'datetime',
        'reminder_sent' => 'boolean',
        'status' => SubscriptionStatus::class,
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function daysUntilExpiry(): int
    {
        return now()->startOfDay()->diffInDays($this->expires_at, false);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::Active->value);
    }

    public function scopeExpiringWithin($query, int $days)
    {
        return $query->active()
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays($days)->toDateString());
    }
}
