<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'seller_id', 'total', 'sold_at', 'notes'];

    protected $casts = ['total' => 'decimal:2', 'sold_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()
            ->where('status', \App\Enums\PaymentStatus::Paid->value)
            ->sum('amount');
    }

    public function balance(): float
    {
        return (float) $this->total - $this->paidAmount();
    }
}
