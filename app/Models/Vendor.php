<?php

namespace App\Models;

use App\Models\VendorImage;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Vendor extends Authenticatable
{
    use HasApiTokens, HasFactory;
    protected $guarded = [];

    public function images()
    {
        return $this->hasMany(VendorImage::class);
    }

    public function mobileListings()
    {
        return $this->hasMany(MobileListing::class);
    }

    public function vendorMobiles()
    {
        return $this->hasMany(VendorMobile::class);
    }

    public function subscription()
    {
        return $this->hasOne(VendorSubscription::class, 'vendor_id')->where('is_active', 1);
    }


	// VendorMobile.php
public function vendor()
{
    return $this->belongsTo(Vendor::class, 'vendor_id', 'id'); 
    // assuming VendorMobile me vendor_id field hai
}


}
