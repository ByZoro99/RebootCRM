<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'label', 'phone_number_id', 'display_number',
        'access_token', 'is_default', 'active',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'is_default' => 'boolean',
        'active' => 'boolean',
    ];

    public static function default(): ?self
    {
        return static::where('active', true)
            ->orderByDesc('is_default')
            ->first();
    }
}
