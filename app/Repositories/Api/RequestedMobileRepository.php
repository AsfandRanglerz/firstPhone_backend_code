<?php

namespace App\Repositories\Api;

use App\Models\MobileRequest;
use carbon\Carbon;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Api\Interfaces\RequestedMobileRepositoryInterface;

class RequestedMobileRepository implements RequestedMobileRepositoryInterface
{
    public function getRequestedMobile()
    {
        $user = Auth::user();

        $vendorLat = $user->latitude;
        $vendorLng = $user->longitude;
        $radius = 50; // Default: 10 km radius

        
        // Fetch mobile requests within the vendor's radius
        $mobileRequests = MobileRequest::with(['brand:id,name', 'model:id,name'])
            ->select(
                'mobile_requests.*',
                DB::raw("
                    (6371 * acos(
                        cos(radians($vendorLat)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians($vendorLng)) +
                        sin(radians($vendorLat)) * sin(radians(latitude))
                    )) AS distance
                ")
            )
        ->having('distance', '<=', $radius)
        ->orderBy('mobile_requests.created_at', 'desc')
        ->orderBy('distance', 'asc')
        ->get()
            ->map(function ($item) {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'brand_name'  => $item->brand?->name,
            'model_name'  => $item->model?->name,
            'location' => $item->location,
            'latitude' => $item->latitude,
            'longitude' => $item->longitude,
            'min_price' => $item->min_price,
            'max_price' => $item->max_price,
            'storage' => $item->storage,
            'ram' => $item->ram,
            'color' => $item->color,
            'condition' => $item->condition,
            'description' => $item->description,
            'status' => $item->status,
            'date'   => Carbon::parse($item->created_at)->format('F d, Y'),
            'time' => $item->created_at->format('h:i A'),
        ];
    });
        return $mobileRequests;
    }
}
