<?php

namespace App\Http\Controllers\Api;

use App\Models\Recentsearch;
use Illuminate\Http\Request;
use App\Models\MobileListing;
use App\Models\SearchHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MobileSearchController extends Controller
{
    public function search(Request $request)
    {
        try {
            $user = Auth::user();
            $query = trim($request->input('name'));
            $customerLat = $request->input('latitude');
            $customerLng = $request->input('longitude');


            if (!$query) {
                return response()->json([
                    'message' => 'Search query required',
                    'data' => []
                ], 400);
            }

            $words = explode(' ', strtolower($query)); // Split query into words

            // Search vendor_mobiles with model, brand, vendor
            $mobiles = \App\Models\VendorMobile::with(['model', 'brand', 'vendor'])
                ->where('stock', '>', 0)
                ->where('status', '==', 0)
                ->where(function ($q) use ($words) {
                    foreach ($words as $word) {
                        $q->where(function ($q2) use ($word) {
                            $q2->whereHas('model', function ($q3) use ($word) {
                                $q3->whereRaw('LOWER(name) LIKE ?', ["%$word%"]);
                            })
                            ->orWhereHas('brand', function ($q3) use ($word) {
                                $q3->whereRaw('LOWER(name) LIKE ?', ["%$word%"]);
                            });
                        });
                    }
                })
                // Filter VendorMobile directly by 50 km radius
                ->whereRaw("
                    (6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    )) <= ?
                ", [$customerLat, $customerLng, $customerLat, 50])
                ->select('id', 'vendor_id', 'model_id', 'brand_id', 'price', 'image')
                ->get();
            
            // If no result found → send empty array instead of 404
            if ($mobiles->isEmpty()) {
                return response()->json([
                    'message' => 'No matching mobile found',
                    'data'    => []
                ], 200);
            }

            // Add to recent search only if user is authenticated
            if ($user) {
                $this->addToRecentSearch($query, $user->id, $mobiles);
            }

            // Format response
            $response = $mobiles->map(function ($m) use ($customerLat, $customerLng) {
                $images = json_decode($m->image, true) ?? [];

                $distance = null;

                if ($customerLat && $customerLng && $m->vendor?->latitude && $m->vendor?->longitude) {
                    $theta = $customerLng - $m->vendor->longitude;
                    $dist = sin(deg2rad($customerLat)) * sin(deg2rad($m->vendor->latitude)) +
                            cos(deg2rad($customerLat)) * cos(deg2rad($m->vendor->latitude)) *
                            cos(deg2rad($theta));
                    $dist = acos($dist);
                    $dist = rad2deg($dist);
                    $distance = round($dist * 60 * 1.1515 * 1.609344, 2); // KM
                }

                return [
                    'id'            => $m->id,
                    'model_id'      => $m->model_id,
                    'model'         => $m->model?->name,
                    'brand_id'      => $m->brand_id,
                    'brand'         => $m->brand?->name,
                    'price'         => $m->price,
                    'image'         => isset($images[0]) ? $images[0] : null,

                    'vendor'       => $m->vendor?->name,
                    'repair_service'    => $m->vendor?->repair_service,
                    'distance'        => $distance ? $distance . ' km' : null,
                ];
            });

            return response()->json([
                'message' => 'Search results',
                'data' => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong while searching',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add recent search (with model_id & brand_id)
     */
    private function addToRecentSearch($query, $userId, $mobiles)
    {
        try {
            // Take first matching mobile for storing model_id & brand_id
            $firstMobile = $mobiles->first();

            if ($firstMobile) {
                \App\Models\Recentsearch::create([
                    'user_id'   => $userId,
                    'model'     => $query,
                    'model_id'  => $firstMobile->model_id,
                    'brand_id'  => $firstMobile->brand_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Something went wrong while adding recent search: ' . $e->getMessage());
        }
    }

    public function searchHistory(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated',
                    'data'    => []
                ], 401);
            }

            $query = trim($request->input('name'));

            if (!$query) {
                return response()->json([
                    'message' => 'Search query required',
                    'data'    => []
                ], 400);
            }

            $words = explode(' ', strtolower($query));

            // SEARCH MAIN DATA
            $mobiles = \App\Models\VendorMobile::with(['model', 'brand', 'vendor'])
                ->where('stock', '>', 0)
                ->where(function ($q) use ($words) {
                    foreach ($words as $word) {
                        $q->where(function ($q2) use ($word) {
                            $q2->whereHas('model', function ($q3) use ($word) {
                                $q3->whereRaw('LOWER(name) LIKE ?', ["%$word%"]);
                            })
                            ->orWhereHas('brand', function ($q3) use ($word) {
                                $q3->whereRaw('LOWER(name) LIKE ?', ["%$word%"]);
                            });
                        });
                    }
                })
                ->select('id', 'vendor_id', 'model_id', 'brand_id', 'price', 'image')
                ->get();

            // SAVE Current search to history table
            $historyEntry = $this->addToSearchHistory($query, $user->id, $mobiles);

            // GET FULL SEARCH HISTORY LIST
            $fullHistory = \App\Models\SearchHistory::with(['model', 'brand'])
                ->where('user_id', $user->id)
                ->orderBy('id', 'DESC')
                ->get()
                ->map(function ($h) {
                    return [
                        'query' => $h->query,
                        'model' => $h->model?->name,
                        'brand' => $h->brand?->name
                    ];
                });

            // FORMAT SEARCHED MOBILES
            $responseMobiles = $mobiles->map(function ($m) {
                $images = json_decode($m->image, true) ?? [];

                return [
                    'id'              => $m->id,
                    'model_id'        => $m->model_id,
                    'model'           => $m->model?->name,
                    'brand_id'        => $m->brand_id,
                    'brand'           => $m->brand?->name,
                    'price'           => $m->price,
                    'image'           => isset($images[0]) ? asset($images[0]) : null,
                    'vendor'          => $m->vendor?->name,
                    'repair_service'  => $m->vendor?->repair_service,
                ];
            });

            return response()->json([
                'message'           => 'Search results',

                // USER INPUT SEARCH
                'searched_input'    => $query,

                // FULL HISTORY FROM TABLE
                'searched_history'  => $fullHistory,

                // ACTUAL CURRENT SEARCH RESULT
                'search_results'    => $responseMobiles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Something went wrong while searching history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function addToSearchHistory($query, $userId, $mobiles)
    {
        try {
            $first = $mobiles->first();

            return \App\Models\SearchHistory::create([
                'user_id'  => $userId,
                'query'    => $query,
                'model_id' => $first?->model_id,
                'brand_id' => $first?->brand_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving search history: ' . $e->getMessage());
            return null;
        }
    }

    public function getRecentSearches()
    {
        try {
            // Current logged-in user ki ID
            $userId = Auth::id();

            // Agar user login nahi hai
            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Database se user ki recent searches get karna
            $recentSearches = DB::table('recentsearches')
                ->where('user_id', $userId)
                ->select('id', 'model', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $recentSearches
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSearchHistory()
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get search history with model & brand names
            $history = \App\Models\SearchHistory::with(['model', 'brand'])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($h) {
                    return [
                        'id'         => $h->id,
                        'name'      => $h->query,
                        'model_name' => $h->model?->name,
                        'brand_name' => $h->brand?->name,
                        'created_at' => $h->created_at,
                    ];
                });

            return response()->json([
                'status' => true,
                'data'   => $history
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            $id = trim($request->input('id'));

            if (empty($id)) {
                return response()->json([
                    'message' => 'ID is required',
                ], 400);
            }

            $find = SearchHistory::find($id);

            if (!$find) {
                return response()->json([
                    'message' => 'History not found',
                ], 404);
            }

            $find->delete();

            return response()->json([
                'message' => 'Deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function deleteAll(Request $request)
    {
        try {
            // Table truncate karna (poora data remove)
            SearchHistory::truncate();

            return response()->json([
                'message' => 'All history deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
