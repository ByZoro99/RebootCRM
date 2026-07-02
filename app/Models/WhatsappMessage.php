<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_number_id', 'customer_id', 'to', 'direction',
        'type', 'body', 'wa_message_id', 'status', 'error',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
