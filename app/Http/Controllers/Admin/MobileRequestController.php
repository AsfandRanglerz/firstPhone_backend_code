<?php

namespace App\Http\Controllers\Admin;

use App\Models\Vendor;
use Illuminate\Http\Request;
use App\Models\MobileRequest;
use App\Models\VendorMobile;
use App\Http\Controllers\Controller;

class MobileRequestController extends Controller
{
    public function index() 
    {
        $mobilerequests = MobileRequest::with('brand', 'model', 'customer')->latest()->get();
        return view('admin.mobilerequest.index', compact('mobilerequests'));
    } 

    public function show($id)
    { 
        $mobilerequests = MobileRequest::with(['brand', 'model'])->findOrFail($id);
        $vendors = VendorMobile::with('vendor')
        ->where('brand_id', $mobilerequests->brand_id)
        ->where('model_id', $mobilerequests->model_id)
        ->where('condition', $mobilerequests->condition)
        ->get()
        ->pluck('vendor')
        ->unique('id') 
        ->values();
        return view('admin.mobilerequest.show', compact('mobilerequests', 'vendors'));
    }

    public function mobileRequestCounter()
    {
        $count = MobileRequest::where('status', 2)->count();
        return response()->json(['count' => $count]);
    }

    public function markAsRead($id)
{
    $mobilerequest = MobileRequest::findOrFail($id);

    if ($mobilerequest->status == 2) {
        $mobilerequest->update(['status' => 0]); 
    }

    return redirect()->back()->with('success', 'Mobile Request Marked As Read');
}

    
    public function delete($id)
    {
        $mobilerequest = MobileRequest::findOrFail($id);
        $mobilerequest->delete();
        return redirect()->route('mobilerequest.index')->with('success', 'Mobile Request Deleted Successfully');
    }
}
