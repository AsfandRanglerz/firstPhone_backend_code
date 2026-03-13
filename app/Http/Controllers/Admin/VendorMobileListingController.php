<?php

namespace App\Http\Controllers\Admin;

use App\Models\VendorMobile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VendorMobileListingController extends Controller
{
     public function index()
    {
        $mobiles = VendorMobile::with('model','brand', 'vendor')->latest()->get();
        return view('admin.vendormobilelisting.index', compact('mobiles'));
    }

   public function show($id)
{
    $mobiles = collect([VendorMobile::findOrFail($id)]);
    return view('admin.vendormobilelisting.show', compact('mobiles'));
}

    public function delete($id)
{
    $mobile = VendorMobile::findOrFail($id); 
    $mobile->delete();
    return redirect()->route('vendormobile.index')->with('success', 'Vendor Mobile Deleted Successfully');
}

    
}
