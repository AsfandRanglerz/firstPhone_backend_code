<?php

namespace App\Models;

use App\Models\Brand;
use App\Models\MobileModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MobileListing extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function model()
    {
        return $this->belongsTo(MobileModel::class, 'model_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function deviceReceipts()
    {
        return $this->hasMany(DeviceReceipt::class, 'product_id');
    }

    public function customer()
    { 
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }


    // protected $appends = ['brand_name', 'model_name'];

    // public function getBrandNameAttribute()
    // {
    //     return $this->brand ? $this->brand->name : null;
    // }

    // public function getModelNameAttribute()
    // {
    //     return $this->model ? $this->model->name : null;
    // }

    public function carts()
{
    return $this->hasMany(MobileCart::class, 'mobile_listing_id');
}

    
}
