<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    protected $fillable = [
        'platform_id', 'email', 'password', 'profiles_total',
        'status', 'purchased_at', 'cost', 'notes',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'status' => AccountStatus::class,
        'purchased_at' => 'date',
        'cost' => 'decimal:2',
    ];

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }
}
