<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Traits\SendsBulkEmails;
use App\Models\MobileListing;
use App\Models\Brand;
use App\Models\Vendor;
use App\Models\User;
use App\Helpers\NotificationHelper;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Models\MobileModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Mail\NotifyNearByVendorsOfListedMobile;
use App\Http\Controllers\Controller;

class MobileListingController extends Controller
{
    use SendsBulkEmails;
    public function index()
    {
        $mobiles = MobileListing::with('model','brand', 'customer')->latest()->get();
        return view('admin.mobilelisting.index', compact('mobiles'));
    }

   public function show($id)
{
    $mobiles = collect([MobileListing::findOrFail($id)]);
    return view('admin.mobilelisting.show', compact('mobiles'));
}


    public function mobileListingCounter()
    {
        $count = MobileListing::where('status', 2)->count(); 
        return response()->json(['count' => $count]);
    }

     public function approve($id)
    {
        $mobile = MobileListing::findOrFail($id);
        $mobile->status = 0; // 0 = Approved
        $mobile->save();
        // notify customer for approval
        $customer = User::find($mobile->customer_id);
            $notification = Notification::create([
                    'user_type' => 'customers',
                    'title' => "Requested New Mobile Listing",
                    'description' => "Good news! Your mobile listing of {$mobile->brand} {$mobile->model} is approved and listed.",
            ]);
            NotificationTarget::create([
                    'notification_id' => $notification->id,
                    'targetable_id' => $customer->id,
                    'targetable_type' => 'App\Models\User',
                    'type' => 'customer_mobile_listed_status',
                ]);
            if (!empty($customer->fcm_token)) {
                    NotificationHelper::sendFcmNotification(
                        $customer->fcm_token,
                        "Requested New Mobile Listing",
                        "Good news! Your mobile listing of {$mobile->brand} {$mobile->model} is approved and listed.",
                        [
                            'type' => 'customer_mobile_listed_status',
                            'order_id' => (string) $mobile->id,
                        ]
                    );
            }
        // notify Vendors within radius
        try {
            $lat = (float) $mobile->latitude;
            $lng = (float) $mobile->longitude;
            $radius = 50;

            // Filter vendors jinka lat/lng null na ho
            $vendors = Vendor::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereNotNull('fcm_token')
                ->select('*', DB::raw("6371 * acos(
                    cos(radians($lat)) 
                    * cos(radians(latitude)) 
                    * cos(radians(longitude) - radians($lng)) 
                    + sin(radians($lat)) 
                    * sin(radians(latitude))
                ) AS distance"))
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->get();
                
                // Send email notifications
                $mobile->customer_name = User::find($mobile->customer_id)->name;
                $mobile->brand_name = $mobile->brand;
                $mobile->model_name = $mobile->model;
                $this->sendBulkEmails($vendors->all(),NotifyNearByVendorsOfListedMobile::class,['data' => $mobile]);
                $notification = Notification::create([
                        'user_type' => 'vendors',
                        'title' => "New Mobile Listing",
                        'description' => "A nearby customer has listed a {$mobile->brand} {$mobile->model} for sale. Check it out!",
                    ]);
            foreach ($vendors as $vendor) {
                try {
                    NotificationTarget::create([
                        'notification_id' => $notification->id,
                        'targetable_id' => $vendor->id,
                        'targetable_type' => 'App\Models\Vendor',
                        'type' => 'customer_mobile_listed',
                    ]);
                    NotificationHelper::sendFcmNotification(
                        $vendor->fcm_token,
                        "New Mobile Listing",
                        "A nearby customer has listed a {$mobile->brand} {$mobile->model} for sale. Check it out!",
                        [
                            'type' => 'customer_mobile_listed',
                            'order_id' => (string) $mobile->id,
                            'price' => (string) $mobile->price,
                        ]
                    );
                } catch (\Exception $fcmError) {
                    \Log::error('FCM Error for vendor ID: ' . $vendor->id, [
                        'error' => $fcmError->getMessage()
                    ]);
                    // continue so service crash na ho
                    continue;
                }
            }

        } catch (\Exception $e) {
            \Log::error('Vendor distance/notification error', [
                'error' => $e->getMessage()
            ]);
            // IMPORTANT: service ko crash na karo
        }
        return redirect()->route('mobile.index')->with('success', 'Mobile Listing Approved Successfully');
    }
 
   public function reject($id)
    {
        $mobile = MobileListing::findOrFail($id);
        $mobile->status = 1; // 1 = Rejected
        $mobile->save();

        return redirect()->route('mobile.index')->with('success', 'Mobile Listing Rejected');
    }

    public function delete($id)
    {
        $mobile = MobileListing::findOrFail($id); 
        $mobile->delete();
        return redirect()->route('mobile.index')->with('success', 'Customer Mobile Deleted Successfully');
    }

    

    
}
