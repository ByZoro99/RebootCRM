<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    /** @use HasFactory<\Database\Factories\PlatformFactory> */
    use HasFactory;

    protected $fillable = ['name', 'base_price', 'profiles_per_account', 'active'];

    protected $casts = [
        'base_price' => 'decimal:2',
        'active' => 'boolean',
    ];

    protected $attributes = [
        'active' => true,
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }
}
