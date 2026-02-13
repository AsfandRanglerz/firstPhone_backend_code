<?php

namespace App\Http\Controllers\Api;

use App\Models\VendorMobile;
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
        $listings = VendorMobile::with('brand')
            ->where('vendor_id', $vendor)
            ->get()
            ->map(function ($listing) {
                $images = json_decode($listing->image, true) ?? [];
                $firstImage = !empty($images) ? asset($images[0]) : null;
                return [
                    'id' => $listing->id,
                    'brand' => $listing->brand ? $listing->brand->name : null,
                    'vendor' => $listing->vendor->name,
                    'price' => $listing->price,
                    'stock' => $listing->stock,
                    'sold' => $listing->sold,
                    // 'image' => $listing->image ? array_map(function ($path) {
                    //     return asset($path);
                    // }, json_decode($listing->image, true) ?? []) : [],
                    'image' => $firstImage,
                    'status' => $listing->status,
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
    } catch (\Exception $e) {
        return ResponseHelper::error($e->getMessage(), 'Failed to mark listing as sold', 'server_error', 500);
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
