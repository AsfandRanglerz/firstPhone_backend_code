<?php

namespace App\Http\Controllers\Api;

use App\Traits\SendsBulkEmails;
use Carbon\Carbon;
use App\Models\Brand;
use App\Models\Vendor;
use App\Models\MobileModel;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\MobileRequest;
use App\Helpers\ResponseHelper;
use App\Models\Notification;
use App\Models\NotificationTarget;
use Illuminate\Support\Facades\DB;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Mail\NotifyNearByVendorsOfRequestedMobile;
use Illuminate\Validation\ValidationException;
use App\Repositories\Api\Interfaces\RequestedMobileRepositoryInterface;

class RequestFormController extends Controller
{
    use SendsBulkEmails;

    protected $requestedMobileRepository;

    public function __construct(RequestedMobileRepositoryInterface $requestedMobileRepository)
    {
        $this->requestedMobileRepository = $requestedMobileRepository;
    }



   public function mobilerequestform(Request $request)
    {
        $errors = [];

        try {
            $user = Auth::user();

            // Brand
            try {
                $brand = Brand::firstOrCreate(
                    ['name' => trim($request->brand_name)],
                    ['slug' => Str::slug($request->brand_name)]
                );
            } catch (\Exception $e) {
                $errors['brand'] = "Brand creation error: " . $e->getMessage();
            }

            // Model
            try {
                $model = MobileModel::firstOrCreate(
                    [
                        'name' => trim($request->model_name),
                        'brand_id' => $brand->id ?? null
                    ]
                );
            } catch (\Exception $e) {
                $errors['model'] = "Model creation error: " . $e->getMessage();
            }

            // Mobile Request
            try {
                $mobileRequest = MobileRequest::create([
                    'customer_id' => $user->id,
                    'name'        => $user->name, 
                    'location'    => $request->location,
                    'brand_id'    => $brand->id ?? null,
                    'model_id'    => $model->id ?? null,
                    'min_price'   => $request->min_price,
                    'max_price'   => $request->max_price,
                    'storage'     => $request->storage,
                    'ram'         => $request->ram,
                    'color'       => $request->color,
                    'condition'   => $request->condition,
                    'description' => $request->description,
                    'latitude'    => $request->latitude,
                    'longitude'   => $request->longitude,
                    'created_at' => Carbon::now('Asia/Karachi'),
                ]);
            } catch (\Exception $e) {
                $errors['mobile_request'] = "Mobile request save error: " . $e->getMessage();
            }


            // Vendors within radius
            $lat = $request->latitude;
            $lng = $request->longitude;
            $radius = 50;
            $condition = $request->condition === 'New' ? 'brand-new' : 'Used';
            try {
                $vendors = Vendor::select('*', DB::raw("6371 * acos(cos(radians($lat)) 
                        * cos(radians(latitude)) 
                        * cos(radians(longitude) - radians($lng)) 
                        + sin(radians($lat)) 
                        * sin(radians(latitude))) AS distance"))
                    ->having('distance', '<=', $radius)
                    ->orderBy('distance')
                    ->get();
            } catch (\Exception $e) {
                $errors['vendors'] = "Vendor query error: " . $e->getMessage();
                $vendors = collect(); // empty collection if query fails
            }
            $mobileRequest->customer_name = $user->name;
            $mobileRequest->brand_name = Brand::find($mobileRequest->brand_id)->name ?? 'Unknown Brand';
            $mobileRequest->model_name = MobileModel::find($mobileRequest->model_id)->name ?? 'Unknown Model';
            // return $mobileRequest;
            // Send email notifications
            $this->sendBulkEmails($vendors->all(),NotifyNearByVendorsOfRequestedMobile::class,['data' => $mobileRequest]);
            // Notify all unique vendors DB insert + FCM
            $notification = Notification::create([
                    'user_type' => 'vendors',
                    'title' => "New Mobile Request",
                    'description' =>  "A nearby customer is looking for a {$condition} {$brand->name} {$model->name}. Add inventory now to capture this sale.",
                ]);
            // Send fcm notifications
            foreach ($vendors as $vendor) {
                try {
                NotificationTarget::create([
                    'notification_id' => $notification->id,
                    'targetable_id' => $vendor->id,
                    'targetable_type' => 'App\Models\Vendor',
                    'type' => 'mobile_request',
                ]);
                    if (!empty($vendor->fcm_token)) {
                        NotificationHelper::sendFcmNotification(
                            $vendor->fcm_token,
                            "New Mobile Request",
                            "A nearby customer is looking for a {$condition} {$brand->name} {$model->name}. Add inventory now to capture this sale.",
                            [
                                'type' => 'mobile_request',
                                'order_id' => (string) $mobileRequest->id,
                                'min_price'  => (string) $mobileRequest->min_price,
                                'max_price'  => (string) $mobileRequest->max_price,
                                'distance'   => (string) round($vendor->distance, 2) . " km"
                            ] 
                        );
                    }
                } catch (\Exception $e) {
                    $errors['notifications'][$vendor->id] = "Notification error: " . $e->getMessage();
                }
            }

            return ResponseHelper::success(
                $mobileRequest ?? null,
                "Mobile request submitted successfully & vendors notified (within {$radius} km)",
                $errors ?: null,
                200
            );

        } catch (\Exception $e) {
            return ResponseHelper::error(
                [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine()
                ],
                'Unexpected error',
                'error',
                500
            );
        }
    }

    public function getRequestedMobile()
    {
        try {
            $mobileRequests = $this->requestedMobileRepository->getRequestedMobile();
             if ($mobileRequests->isEmpty()) {
                return ResponseHelper::error(null, 'No requested mobiles found within 10 km radius', 'not_found', 404);
            }
            return ResponseHelper::success($mobileRequests, 'Requests fetched successfully', null, 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred while fetching requests', 'error', 500);
        }
    }
}
