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
//         // REQUIRED FIELDS
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
//         $query = VendorMobile::with('vendor', 'model')
//             ->where('brand_id', $request->brand_id)
//             ->where('model_id', $request->model_id);

//         // OPTIONAL FILTERS
//         if ($request->filled('repair_service')) {
//             $query->whereHas('vendor', function ($q) use ($request) {
//                 $q->where('repair_service', $request->repair_service);
//             });
//         }
//         if ($request->filled('storage'))   $query->where('storage', $request->storage);
//         if ($request->filled('ram'))       $query->where('ram', $request->ram);
//         if ($request->filled('condition')) $query->where('condition', $request->condition);
//         if ($request->filled('color'))     $query->where('color', $request->color);

//         // ---------------------------
//         // PRICE FILTERING USING LIKE
//         // ---------------------------
//         $min = $request->min_price;
//         $max = $request->max_price;

//         if ($min !== null && $min !== '') $min = trim($min);
//         else $min = null;

//         if ($max !== null && $max !== '') $max = trim($max);
//         else $max = null;

//         if ($min && $max) {
//             if ($min > $max) [$min, $max] = [$max, $min]; // swap if needed
//             $query->where(function ($q) use ($min, $max) {
//                 $q->whereRaw("CAST(price AS CHAR) LIKE ?", ["%$min%"])
//                   ->orWhereRaw("CAST(price AS CHAR) LIKE ?", ["%$max%"]);
//             });
//         } elseif ($min) {
//             $query->whereRaw("CAST(price AS CHAR) LIKE ?", ["%$min%"]);
//         } elseif ($max) {
//             $query->whereRaw("CAST(price AS CHAR) LIKE ?", ["%$max%"]);
//         }

//         // ---------------------------
//         // FETCH ALL RESULTS
//         // ---------------------------
//       $listings = $query->get();
//         // ---------------------------
//         // LOCATION LOGIC
//         // ---------------------------
//         $cityMode   = $request->filled('city');
//         $radiusMode = $request->filled('latitude') && $request->filled('longitude');

//         if ($cityMode) {
//             $city = $request->city;
//             $listings = $listings->filter(fn($item) => ($item->location ?? null) === $city)->values();
//         } elseif ($radiusMode) {
//             $lat    = $request->latitude;
//             $lng    = $request->longitude;
//             $radius = $request->radius ?? 50;

//             $listings = $listings->filter(function ($item) use ($lat, $lng, $radius) {
//                 if (!$item->vendor?->latitude || !$item->vendor?->longitude) return false;

//                 $theta = $lng - $item->vendor->longitude;
//                 $dist = sin(deg2rad($lat)) * sin(deg2rad($item->vendor->latitude)) +
//                         cos(deg2rad($lat)) * cos(deg2rad($item->vendor->latitude)) * cos(deg2rad($theta));
//                 $dist = acos($dist);
//                 $dist = rad2deg($dist);
//                 $miles = $dist * 60 * 1.1515;
//                 $km = $miles * 1.609344;

//                 return $km <= $radius;
//             })->values();
//         }

// 		 $listings = $query->get();
//         // ---------------------------
//         // RETURN RESULTS
//         // ---------------------------
//         return response()->json([
//             'status' => 'success',
//             'data'   => $listings
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
            // if (!$request->filled('brand_id') || !$request->filled('model_id')) {
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'Please enter required details first'
            //     ], 400);
            // }

            // ---------------------------
            // BASE QUERY
            // ---------------------------
            $query = VendorMobile::with('vendor', 'model', 'brand')
                ->when($request->brand_id, function ($q) use ($request) {
                    $q->where('brand_id', $request->brand_id);
                })
                ->when($request->model_id, function ($q) use ($request) {
                    $q->where('model_id', $request->model_id);
                })
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
                    $radius = $hasRadius ? (float)$request->radius : 50; // default 50 km
                    $listings = $listings->filter(function ($item) use ($latReq, $lngReq, $radius) {

                        if (!$item->vendor?->latitude || !$item->vendor?->longitude) return false;

                        // Haversine formula
                        $earthRadius = 6371; // km

                        $latFrom = deg2rad($latReq);
                        $lngFrom = deg2rad($lngReq);
                        $latTo   = deg2rad($item->vendor->latitude);
                        $lngTo   = deg2rad($item->vendor->longitude);

                        $dLat = $latTo - $latFrom;
                        $dLng = $lngTo - $lngFrom;

                        $a = sin($dLat/2) * sin($dLat/2) +
                            cos($latFrom) * cos($latTo) *
                            sin($dLng/2) * sin($dLng/2);

                        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                        $distance = $earthRadius * $c;

                        return $distance <= $radius;
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
                    'brand'     => $item->brand->name ?? null,
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
