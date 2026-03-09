<?php

namespace App\Http\Controllers\Api;

use App\Models\Brand;
use App\Models\MobileModel;
use App\Models\VendorMobile;
use Illuminate\Http\Request;
use App\Models\MobileListing;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;

class MobileFilterController extends Controller
{
     /**
     * Get all brands
     */
    public function getBrands()
    {
        try {
            $brands = Brand::all();

            return ResponseHelper::success(
                $brands,
                'Brands fetched successfully'
            );

        } catch (\Exception $e) {
            \Log::error('Error fetching brands: ' . $e->getMessage());

            return ResponseHelper::error(
                null,
                'Failed to fetch brands',
                'server_error',
                500
            );
        }
    }

    /**
     * Get models by brand_id
     */
    public function getModels($brand_id)
    {
        try {
            $models = MobileModel::where('brand_id', $brand_id)->get();

            if ($models->isEmpty()) {
                return ResponseHelper::error(
                    null,
                    'No Models Found For This Brand',
                    'not_found',
                    200
                );
            }

            return ResponseHelper::success(
                $models,
                'Mobile models fetched successfully'
            );

        } catch (\Exception $e) {
            \Log::error('Error fetching models for brand ID ' . $brand_id . ': ' . $e->getMessage());

            return ResponseHelper::error(
                null,
                'Failed to fetch mobile models',
                'server_error',
                500
            );
        }
    }

    /**
     * Get filtered listings by brand, model, storage, ram, etc.
	 */

// public function getData(Request $request)
// {
//     try {
//         // ---------------------------
//         // BRAND & MODEL REQUIRED
//         // ---------------------------
//         if (!$request->filled('brand_id') || !$request->filled('model_id')) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'Please enter required details first'
//             ], 400);
//         }

//         // ---------------------------
//         // BASE QUERY
//         // ---------------------------
//         $query = VendorMobile::with(['vendor', 'model'])
//             ->join('vendors', 'vendors.id', '=', 'vendor_mobiles.vendor_id');

//         // Mandatory filters (vendor_mobiles)
//         $query->where('vendor_mobiles.brand_id', $request->brand_id)
//               ->where('vendor_mobiles.model_id', $request->model_id);

//         // ---------------------------
//         // REPAIR SERVICE FILTER (vendor table)
//         // ---------------------------
        
//          $query->where('vendors.repair_service', $request->repair_service); // checkbox selected
        

//         // ---------------------------
//         // LOCATION FILTER LOGIC
//         // ---------------------------
//         $userLat = $request->latitude;
//         $userLng = $request->longitude;
//         $radius  = $request->radius;
//         $city    = $request->city;

//         $isRadiusMode = $request->filled('radius') && $request->filled('latitude') && $request->filled('longitude');
//         $isCityMode   = $request->filled('city');

//         if ($isRadiusMode) {
//             // RADIUS MODE → CITY IGNORE
//             $query->select('vendor_mobiles.*', 'vendors.latitude', 'vendors.longitude', 'vendors.location')
//                   ->selectRaw("
//                     (6371 * acos(
//                         cos(radians(?)) *
//                         cos(radians(vendors.latitude)) *
//                         cos(radians(vendors.longitude) - radians(?)) +
//                         sin(radians(?)) *
//                         sin(radians(vendors.latitude))
//                     )) AS distance
//                   ", [$userLat, $userLng, $userLat])
//                   ->having('distance', '<=', $radius)
//                   ->orderBy('distance');
//         } elseif ($isCityMode) {
//             // CITY MODE → RADIUS IGNORE
//             $query->where('vendors.location', $city);
//         }

//         // ---------------------------
//         // OPTIONAL FILTERS (vendor_mobiles)
//         // ---------------------------
//         if ($request->filled('storage'))   $query->where('vendor_mobiles.storage', $request->storage);
//         if ($request->filled('ram'))       $query->where('vendor_mobiles.ram', $request->ram);
//         if ($request->filled('condition')) $query->where('vendor_mobiles.condition', $request->condition);
//         if ($request->filled('color'))     $query->where('vendor_mobiles.color', $request->color);

//         // ---------------------------
//         // PRICE FILTERS
//         // ---------------------------
//         if ($request->filled('min_price') && $request->filled('max_price')) {
//             $query->whereBetween('vendor_mobiles.price', [$request->min_price, $request->max_price]);
//         } elseif ($request->filled('min_price')) {
//             $query->where('vendor_mobiles.price', '>=', $request->min_price);
//         } elseif ($request->filled('max_price')) {
//             $query->where('vendor_mobiles.price', '<=', $request->max_price);
//         }

//         // ---------------------------
//         // FETCH DATA
//         // ---------------------------
//         $listings = $query->get();

//         // EMPTY CHECK FOR RADIUS MODE
//         if ($isRadiusMode && $listings->isEmpty()) {
//             return response()->json([
//                 'status' => 'success',
//                 'data'   => []
//             ], 200);
//         }

//         // CITY MODE EMPTY CHECK
//         if (!$isRadiusMode && $listings->isEmpty()) {
//             $checkBase = VendorMobile::where('brand_id', $request->brand_id)
//                 ->where('model_id', $request->model_id)
//                 ->exists();

//             return response()->json([
//                 'status'  => 'not_found',
//                 'message' => $checkBase ? 'Mobile not found' : 'No Mobile Found'
//             ], 404);
//         }

//         // ---------------------------
//         // FORMAT OUTPUT
//         // ---------------------------
//         $response = $listings->map(function ($item) {
//             return [
//                 'image'          => $item->image,
//                 'title'          => $item->model->name ?? null,
//                 'price'          => $item->price,
//                 'shop_name'      => $item->shop_name,
//                 'location'       => $item->vendor->location ?? null,
//                 'latitude'       => $item->vendor->latitude ?? null,
//                 'longitude'      => $item->vendor->longitude ?? null,
//                 'condition'      => $item->condition,
//             ];
//         });

//         return response()->json([
//             'status' => 'success',
//             'data'   => $response
//         ], 200);

//     } catch (\Exception $e) {
//         return response()->json([
//             'message' => $e->getMessage()
//         ], 500);
//     }
// }


public function getData(Request $request)
{
    try {
        // ---------------------------
        // REQUIRED FIELDS
        // ---------------------------
        if (!$request->filled('brand_id') || !$request->filled('model_id')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Please enter required details first'
            ], 400);
        }

        // ---------------------------
        // BASE QUERY
        // ---------------------------
        $query = VendorMobile::with('vendor', 'model')
            ->where('brand_id', $request->brand_id)
            ->where('model_id', $request->model_id)
            ->where('status', 0);

        // ---------------------------
        // OPTIONAL FILTERS
        // ---------------------------
        if ($request->filled('repair_service')) {
            $query->whereHas('vendor', function ($q) use ($request) {
                $q->where('repair_service', $request->repair_service);
            });
        }
        if ($request->filled('storage'))   $query->where('storage', $request->storage);
        if ($request->filled('ram'))       $query->where('ram', $request->ram);
        if ($request->filled('condition')) $query->where('condition', $request->condition);
        if ($request->filled('color'))     $query->where('color', $request->color);

        // ---------------------------
        // PRICE FILTER (LIKE)
        // ---------------------------
        // ---------------------------
        // PRICE FILTER (BETWEEN)
        // ---------------------------
        if ($request->filled('min_price') && $request->filled('max_price')) {

            $min = (int) $request->min_price;
            $max = (int) $request->max_price;

            if ($min > $max) {
                [$min, $max] = [$max, $min];
            }

            $query->whereBetween('price', [$min, $max]);

        } elseif ($request->filled('min_price')) {

            $query->where('price', '>=', (int) $request->min_price);

        } elseif ($request->filled('max_price')) {

            $query->where('price', '<=', (int) $request->max_price);
        }


        // ---------------------------
        // FETCH RESULTS
        // ---------------------------
        $listings = $query->get();

        // ---------------------------
        // LOCATION LOGIC
        // ---------------------------
        $radius = null;
        $hasCity   = $request->filled('city');
        $hasLatLng = $request->filled('latitude') && $request->filled('longitude');
        $hasRadius = $request->filled('radius');

        $latReq = $request->latitude;
        $lngReq = $request->longitude;

        // CASE 1: City filter takes priority
        if ($hasCity) {
            $city = strtolower($request->city);
            $listings = $listings->filter(function ($item) use ($city) {
                $vendorLocation = $item->vendor->location ?? '';
                // case-insensitive search
                return stripos($vendorLocation, $city) !== false;
            })->values();
        }
        // CASE 2: Latitude/longitude with optional radius
        elseif ($hasLatLng) {
            $radius = $hasRadius ? $request->radius : 50; // default 50 km
            $listings = $listings->filter(function ($item) use ($latReq, $lngReq, $radius) {
                if (!$item->vendor?->latitude || !$item->vendor?->longitude) return false;

                $theta = $lngReq - $item->vendor->longitude;
                $dist = sin(deg2rad($latReq)) * sin(deg2rad($item->vendor->latitude)) +
                        cos(deg2rad($latReq)) * cos(deg2rad($item->vendor->latitude)) * cos(deg2rad($theta));
                $dist = acos($dist);
                $dist = rad2deg($dist);
                $km   = $dist * 60 * 1.1515 * 1.609344;

                return $km <= $radius;
            })->values();
        }

        // ---------------------------
        // FORMAT RESPONSE
        // ---------------------------
        $formatted = $listings->map(function ($item) use ($radius, $latReq, $lngReq) {
            $images = is_string($item->image) && is_array(json_decode($item->image, true))
            ? json_decode($item->image, true)
            : ($item->image ? [$item->image] : []);
            $distance = null;
            if ($radius !== null && $item->vendor?->latitude && $item->vendor?->longitude) {
                $theta = $lngReq - $item->vendor->longitude;
                $dist = sin(deg2rad($latReq)) * sin(deg2rad($item->vendor->latitude)) +
                        cos(deg2rad($latReq)) * cos(deg2rad($item->vendor->latitude)) * cos(deg2rad($theta));
                $dist = acos($dist);
                $dist = rad2deg($dist);
                $distance = round($dist * 60 * 1.1515 * 1.609344, 2);
            }

            return [
                'id' => $item->id,
                "vendor"    => $item->vendor->name ?? null,
                'model'     => $item->model->name ?? null,
                'price'     => $item->price,
                'distance'  => round($distance) . ' km',
				"repair_service" => $item->vendor->	repair_service ?? null,
                'image'     => array_map(fn ($path) => asset($path), $images),
            ];
        });

        return response()->json([
            'status' => 'success',
            'count'  => $formatted->count(),
            'data'   => $formatted
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
   
}

public function getMinMaxPrice()
{
    try {
        $minPrice = VendorMobile::min('price');
        $maxPrice = VendorMobile::max('price');

        return response()->json([
            'status' => 'success',
            'message' => 'Price range fetched successfully',
            'data' => [
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch price range',
            'error' => $e->getMessage()
        ], 500);
    }
}



}
