<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Mail\NotifyNearByVendorsOfListedMobile;
use App\Models\Brand;
use App\Models\MobileListing;
use App\Models\MobileModel;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Models\User;
use App\Models\UserRolePermission;
use App\Models\Vendor;
use App\Traits\SendsBulkEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MobileListingController extends Controller
{
    use SendsBulkEmails;
    public function index()
    {
        // $mobiles = MobileListing::with('model','brand', 'customer')->latest()->get();
        return view('admin.mobilelisting.index');
    }

    public function getMobileListingsData(Request $request)
{
    $sideMenuPermissions = collect();

    if (!Auth::guard('admin')->check()) {
        $user = Auth::guard('subadmin')->user();
        $roleId = $user->roles->first()->id ?? null;

        $permissions = UserRolePermission::with(['permission', 'sideMenue'])
            ->where('role_id', $roleId)
            ->get();

        $sideMenuPermissions = $permissions->groupBy('sideMenue.name')
            ->map(fn($items) => $items->pluck('permission.name'));
    }

    $query = MobileListing::with(['model','brand','customer'])
        ->latest();

    return datatables()->of($query)

        ->addIndexColumn()

        ->filterColumn('customer_info', function($query, $keyword) {
        $query->whereHas('customer', function($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
            ->orWhere('email', 'like', "%{$keyword}%")
            ->orWhere('phone', 'like', "%{$keyword}%");
        });
    })

        ->filterColumn('created_at', function($query, $keyword) {
        $query->whereRaw(
            "DATE_FORMAT(created_at, '%d %b %Y, %h:%i %p') LIKE ?",
            ["%{$keyword}%"]
        );
    })

        ->filterColumn('brand', function($query, $keyword) {
        $query->where('brand', 'like', "%{$keyword}%"); 
    })

        ->filterColumn('model', function($query, $keyword) {
        $query->where('model', 'like', "%{$keyword}%"); 
    })

        ->filterColumn('ram', function($query, $keyword) {
        $query->where('ram', 'like', "%{$keyword}%");
    })

    ->filterColumn('storage', function($query, $keyword) {
        $query->where('storage', 'like', "%{$keyword}%");
    })

    ->filterColumn('condition', function($query, $keyword) {
        $query->where('condition', 'like', "%{$keyword}%");
    })

    ->filterColumn('location', function($query, $keyword) {
        $query->where('location', 'like', "%{$keyword}%");
    })

     ->filterColumn('price', function($query, $keyword) {
        $query->where('price', 'like', "%{$keyword}%");
    })

    ->filterColumn('about', function($query, $keyword) {
        $query->where('about', 'like', "%{$keyword}%");
    })

    ->filterColumn('status', function($query, $keyword) {

    $keyword = strtolower($keyword);

    $query->where(function($q) use ($keyword) {

        if (str_contains($keyword, 'approved')) {
            $q->orWhere('status', 0);
        }

        if (str_contains($keyword, 'rejected')) {
            $q->orWhere('status', 1);
        }

        if (str_contains($keyword, 'pending')) {
            $q->orWhere('status', 2);
        }

    });
})

        // ✅ Date
        ->editColumn('created_at', function ($mobile) {
            return $mobile->created_at
                ? $mobile->created_at->format('d M Y, h:i A')
                : '';
        })

        // ✅ Name + Email + Phone (same as Blade)
        ->addColumn('customer_info', function ($mobile) {
            $name = $mobile->customer->name ?? '';

            $email = $mobile->customer
                ? '<a href="mailto:'.$mobile->customer->email.'">'.$mobile->customer->email.'</a>'
                : '';

            $phone = $mobile->customer
                ? '<a href="tel:'.$mobile->customer->phone.'">'.$mobile->customer->phone.'</a>'
                : '';

            return $name.'<br>'.$email.'<br>'.$phone;
        })

        // ✅ Location
        ->addColumn('location', function ($mobile) {
            return $mobile->location ?? '';
        })

        // ✅ Brand
        ->addColumn('brand', function ($mobile) {
            return $mobile->brand ?? '<span class="text-muted">No Brand</span>';
        })

        // ✅ Model
        ->addColumn('model', function ($mobile) {
            return $mobile->model ?? '<span class="text-muted">No Model</span>';
        })

        // ✅ RAM
        ->addColumn('ram', function ($mobile) {
            return $mobile->ram ?? '<span class="text-muted">No RAM</span>';
        })

        // ✅ ROM
        ->addColumn('storage', function ($mobile) {
            return $mobile->storage ?? '<span class="text-muted">No ROM</span>';
        })

        // ✅ Price
        ->addColumn('price', function ($mobile) {
            return $mobile->price ?? '<span class="text-muted">No Price</span>';
        })

        // ✅ Condition
        ->addColumn('condition', function ($mobile) {
            return $mobile->condition ?? '<span class="text-muted">No Condition</span>';
        })

        // ✅ About
        ->addColumn('about', function ($mobile) {
            return $mobile->about ?? '';
        })

        // ✅ STATUS (FULL DROPDOWN LIKE BLADE)
        ->addColumn('status', function ($mobile) use ($sideMenuPermissions) {

            if (
                Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Customer Mobiles') &&
                $sideMenuPermissions['Customer Mobiles']->contains('status'))
            ) {

                $status = (int) $mobile->status;

                $statusText = match ($status) {
                    0 => 'Approved',
                    1 => 'Rejected',
                    2 => 'Pending',
                    default => 'Unknown',
                };

                $buttonClass = match ($status) {
                    0 => 'btn-success',
                    1 => 'btn-danger',
                    2 => 'btn-warning',
                    default => 'btn-secondary',
                };

                // Approved → no dropdown
                if ($status == 0) {
                    return '<button class="btn btn-sm '.$buttonClass.'">'.$statusText.'</button>';
                }

                // Dropdown
                $html = '<div class="dropdown">
                    <button class="btn btn-sm dropdown-toggle '.$buttonClass.'" data-toggle="dropdown">
                        '.$statusText.'
                    </button>
                    <div class="dropdown-menu">';

                if ($status == 1) {
                    // Rejected → Approve only
                    $html .= '
                    <form method="POST" action="'.route('mobilelisting.approve', $mobile->id).'">
                        '.csrf_field().'
                        <button type="submit" class="dropdown-item text-success">Approve</button>
                    </form>';
                }

                if ($status == 2) {
                    // Pending → Approve + Reject
                    $html .= '
                    <form method="POST" action="'.route('mobilelisting.approve', $mobile->id).'">
                        '.csrf_field().'
                        <button type="submit" class="dropdown-item text-success">Approve</button>
                    </form>

                    <form method="POST" action="'.route('mobilelisting.reject', $mobile->id).'">
                        '.csrf_field().'
                        <button type="submit" class="dropdown-item text-danger">Reject</button>
                    </form>';
                }

                $html .= '</div></div>';

                return $html;
            }

            return '';
        })

        // ✅ VIEW BUTTON
        ->addColumn('view', function ($mobile) {
            return '<a class="btn btn-primary"
                        href="'.route('mobile.show', $mobile->id).'">View</a>';
        })

        // ✅ ACTIONS (DELETE)
        ->addColumn('actions', function ($mobile) use ($sideMenuPermissions) {

            $buttons = '<div class="d-flex">';

            if (
                Auth::guard('admin')->check() ||
                ($sideMenuPermissions->has('Customer Mobiles') &&
                $sideMenuPermissions['Customer Mobiles']->contains('delete'))
            ) {
                $buttons .= '
                <form id="delete-form-'.$mobile->id.'" 
                    action="'.route('mobile.delete',$mobile->id).'" 
                    method="POST">
                    '.csrf_field().'
                    '.method_field('DELETE').'
                </form>

                <button class="show_confirm btn" 
                    style="background-color: #009245;"
                    data-form="delete-form-'.$mobile->id.'" 
                    type="button">
                    <i class="fa fa-trash"></i>
                </button>';
            }

            $buttons .= '</div>';

            return $buttons;
        })

        ->rawColumns([
            'customer_info','brand','model','ram','storage',
            'price','condition','status','view','actions'
        ])

        ->make(true);
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

        return redirect()->route('mobile.index')->with('success', 'Mobile Listing Rejected Successfully');
    }

    public function delete($id)
    {
        $mobile = MobileListing::findOrFail($id); 
        $mobile->delete();
        return redirect()->route('mobile.index')->with('success', 'Customer Mobile Deleted Successfully');
    }

    

    
}
