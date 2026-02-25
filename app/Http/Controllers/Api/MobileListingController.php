<?php

namespace App\Http\Controllers\Api;

use Log;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\VendorMobile;
use App\Models\VendorSubscription;
use Illuminate\Http\Request;
use App\Models\MobileListing;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Api\MobileListingService;
use Illuminate\Validation\ValidationException;

class MobileListingController extends Controller
{
   protected $mobileListingService;

    public function __construct(MobileListingService $mobileListingService)
    {
        $this->mobileListingService = $mobileListingService;
    }

    
public function getmobileListing()
{
    try{
        $vendor = Auth::id();
        $activeSubscription = VendorSubscription::with('plan')->where('vendor_id', $vendor)
        ->where('is_active', 1)
        ->first();
        $listings = VendorMobile::with(['brand', 'vendor'])
            ->where('vendor_id', $vendor)
            ->get()
            ->map(function ($listing) use ($activeSubscription) {

            $images = json_decode($listing->image, true) ?? [];
            $firstImage = !empty($images) ? asset($images[0]) : null;

            $isActive = false;

            if ($activeSubscription) {
                $isActive = DB::table('vendor_subscription_products')
                    ->where('vendor_subscription_id', $activeSubscription->id)
                    ->where('mobile_id', $listing->id)
                    ->exists();
            }

            return [
                'id' => $listing->id,
                'brand' => $listing->brand?->name,
                'vendor' => $listing->vendor?->name,
                'price' => $listing->price,
                'stock' => $listing->stock,
                'sold' => $listing->sold,
                'image' => $firstImage,
                'status' => $listing->status,
                'plan_status' => $isActive,
                'plan_duration_days' => $activeSubscription ? $activeSubscription->duration_days : null
            ];
        });
        // $data = $listings->count() === 1 ? $listings->first() : $listings;
        return ResponseHelper::success($listings, 'Mobile listings retrieved successfully', null, 200);

    } catch (ValidationException $e) {
        return ResponseHelper::error($e->errors(), 'Validation failed', 'validation_error', 422);
    } catch (\Exception $e) {
        return ResponseHelper::error($e->getMessage(), 'An error occurred while retrieving the listing', 'server_error', 500);
    }
}

public function getNearbyCustomerListings(Request $request)
{
    try {
        $vendor = Auth::user();

        $radius = $request->radius ?? 30; // default 30 km

        $listings = $this->mobileListingService->getcustomernearbyListings(
            $vendor->latitude,
            $vendor->longitude,
            $radius
        );

        return ResponseHelper::success($listings, 'Nearby customer listings fetched successfully', null, 200);

    } catch (\Exception $e) {
        return ResponseHelper::error($e->getMessage(), 'An error occurred while fetching nearby listings', 'server_error', 500);
    }
}

public function getCustomerDeviceDetail($id)
{
    try {
    $device = $this->mobileListingService->getCustomerDeviceDetail($id);
   return ResponseHelper::success($device, 'Device details fetched successfully', null, 200);
    } catch (\Exception $e) {
    return ResponseHelper::error($e->getMessage(), 'An error occurred while fetching device details', 'server_error', 500);
        }

}

public function markAsSold($id)
{
    try {
        
        $customerId = Auth::id();
        // return $customerId;
        $listing = MobileListing::where('id', $id)
            ->where('customer_id', $customerId)
            ->first();
            
        if (!$listing) {
            return ResponseHelper::error(null, 'Listing not found', 'not_found', 404);
        }

        if ($listing->is_sold) {
            return ResponseHelper::error(null, 'This listing is already marked as sold', 'already_sold', 400);
        }

        $listing->is_sold = 1;
        $listing->save();

        return ResponseHelper::success($listing, 'Listing marked as sold successfully');
    } catch (Exception $e) {
    Log::error('Mark as sold error', [
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => $e->getFile()
    ]);

    return ResponseHelper::error(
        $e->getMessage(),
        'Failed to mark listing as sold',
        'server_error',
        500
    );
    }

}

public function customerdeleteMobileListing(Request $request)
{
    $customer = Auth::user();

    if (!$customer) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    $id = $request->query('id');

    // Find cart item
    $mobile = MobileListing::where('id', $id)
        ->where('customer_id', $customer->id) // ensure item belongs to the logged-in user
        ->first();

    if (!$mobile) {
        return response()->json([
            'status' => false,
            'message' => 'Mobile Listing not found',
        ], 404);
    }

    // Delete the record
    $mobile->delete();

    return response()->json([
        'message' => 'Mobile Listing deleted successfully',
    ], 200);
}


}
