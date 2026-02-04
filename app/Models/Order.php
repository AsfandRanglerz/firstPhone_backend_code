<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
    'delivered_at' => 'datetime',
    'shipped_at' => 'datetime',
    ];

     public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function shippingAddress()
    {
        return $this->hasOne(ShippingAddress::class, 'customer_id', 'customer_id');
    }

    public function cancelOrder()
    {
        return $this->hasOne(CancelOrder::class, 'order_id');
    }

}
