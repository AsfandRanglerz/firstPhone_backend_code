<?php

namespace App\Http\Controllers\Api;

use App\Models\VendorMobile;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;
use App\Services\Api\VendorMobileListingService;

class VendorMobileListingController extends Controller
{
    protected $vendormobileListingService;

    public function __construct(VendorMobileListingService $vendormobileListingService)
    {
        $this->vendormobileListingService = $vendormobileListingService;
    }

    public function mobileListing(Request $request)
    {
        try {
            $data = $this->vendormobileListingService->createListing($request);
            return ResponseHelper::success($data, 'Listing added successfully', null, 200);
        } catch (\Exception $e) {
            // dd($e->getMessage());  
            return ResponseHelper::error($e->getMessage(), 'An error occurred while creating the listing', 'server_error', 500);
        }
    }

    public function editListing(Request $request, $id)
    {
        try {
            $data = $this->vendormobileListingService->updateListing($request, $id);

            return ResponseHelper::success(
                $data,
                'Listing updated successfully',
                null,
                200
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::error(
                'Listing not found',
                'Listing not found',
                'not_found',
                404
            );

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return ResponseHelper::error(
                'Unauthorized',
                'Unauthorized',
                'unauthorized',
                403
            );

        } catch (\Exception $e) {
            return ResponseHelper::error(
                $e->getMessage(),
                'Failed to update listing',
                'server_error',
                500
            );
        }
    }

    public function previewListing($id)
    {
        try {
            $data = $this->vendormobileListingService->previewListing($id);
            return ResponseHelper::success($data, 'Preview generated successfully', null, 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'An error occurred while generating preview', 'error', 500);
        }
    }

    public function deleteMobileListing(Request $request)
{
    $user = Auth::user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    $id = $request->query('id');

    // Find cart item
    $mobile = VendorMobile::where('id', $id)
        ->where('vendor_id', $user->id) // ensure item belongs to the logged-in user
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

public function deactivateMobileListing($id)
{
    try {
        $data = $this->vendormobileListingService->deactivateListing($id);

        return ResponseHelper::success(
            $data,
            'Listing deactivated successfully',
            null,
            200
        );

    } catch (\Exception $e) {
        return ResponseHelper::error(
            $e->getMessage(),
            'An error occurred while deactivating the listing',
            'error',
            500
        );
    }
}
}
