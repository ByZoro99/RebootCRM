<?php

namespace App\Models;

use App\Enums\ProfileStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = ['account_id', 'name', 'pin', 'status'];

    protected $casts = ['status' => ProfileStatus::class];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->whereNull('cancelled_at');
    }

    public function isFree(): bool
    {
        return $this->status === ProfileStatus::Free;
    }

    public function scopeFree($query)
    {
        return $query->where('status', ProfileStatus::Free->value);
    }
}
