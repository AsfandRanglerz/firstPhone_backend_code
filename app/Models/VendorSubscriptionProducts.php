<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorSubscriptionProducts extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function mobiles()
    {
        return $this->belongsTo(VendorMobile::class, 'mobile_id');
    }
    public function subscription()
    {
        return $this->belongsTo(VendorSubscription::class, 'vendor_subscription_id');
    }
}
