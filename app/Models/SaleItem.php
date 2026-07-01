<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = ['sale_id', 'platform_id', 'profile_id', 'description', 'price', 'months'];

    protected $casts = ['price' => 'decimal:2'];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
}
