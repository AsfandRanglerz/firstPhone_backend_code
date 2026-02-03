<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorImage;
use App\Mail\VendorRequestForRegister;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SocialLoginController extends Controller
{
    // =====================================
    // MAIN SOCIAL LOGIN (Google + Apple)
    // =====================================
    public function socialLogin(Request $request)
    {
        DB::beginTransaction();
        try {
            $validate = Validator::make($request->all(), [
                'social_id'  => 'required',
                'login_type' => 'required|in:google,apple',
                'type'       => 'required|in:customer,vendor'
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'errors' => $validate->errors()
                ], 422);
            }

            if ($request->type === 'customer') {
                $result = $this->handleCustomer($request);
            } else {
                $result = $this->handleVendor($request);
            }

            DB::commit();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // =====================================
    // CUSTOMER HANDLER (FIXED)
    // =====================================
    protected function handleCustomer(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'email' => 'required|email',
            'name'  => 'required|string|max:255', // nullable se required kiya
            'phone' => 'nullable|string|max:20',
            'image' => 'nullable|url' // URL validation add ki
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $socialColumn = $request->login_type === 'apple' ? 'apple_social_id' : 'google_social_id';

        // Check existing user by social_id FIRST (priority to social login)
        $user = User::where($socialColumn, $request->social_id)->first();

        if (!$user) {
            // Check by email if social_id not found
            $user = User::where('email', $request->email)->first();
        }

        $imagePath = null;
        // Image download and save (fixed)
        if (!empty($data['image']) && filter_var($data['image'], FILTER_VALIDATE_URL)) {
            try {
                $img = @file_get_contents($data['image']);
                if ($img !== false) {
                    $name = uniqid() . "_customer.jpg";
                    $savePath = public_path("admin/assets/images/users/");
                    
                    // Create directory if not exists
                    if (!file_exists($savePath)) {
                        mkdir($savePath, 0755, true);
                    }
                    
                    file_put_contents($savePath . $name, $img);
                    $imagePath = "public/admin/assets/images/users/$name"; // 'public/' hata diya
                }
            } catch (\Exception $e) {
                // Image download fail - continue without image
            }
        }

        if ($user) {
            // Update existing user
            $updateData = [
                'name'       => $data['name'] ?? $user->name,
                'phone'      => $data['phone'] ?? $user->phone,
                'login_type' => $data['login_type'], // ✅ Yeh fix hai - login_type save hoga
                'toggle'     => 1,
                $socialColumn => $data['social_id'] // Social ID bhi update karo
            ];

            if ($imagePath) {
                // Purani image delete karo agar exist karti hai
                if ($user->image && file_exists(public_path($user->image))) {
                    @unlink(public_path($user->image));
                }
                $updateData['image'] = $imagePath;
            }

            $user->update($updateData);

            return response()->json([
                'status'  => true,
                'message' => 'Customer logged in successfully.',
                'user'    => $user,
                'token'   => $user->createToken('auth_token')->plainTextToken
            ]);
        }

        // Create new customer
        $user = User::create([
            'email'       => $data['email'],
            'name'        => $data['name'],
            'phone'       => $data['phone'] ?? null,
            'image'       => $imagePath,
            'login_type'  => $data['login_type'], // ✅ Yeh fix hai
            $socialColumn => $data['social_id'],
            'toggle'      => 1
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Customer registered successfully.',
            'user'    => $user,
            'token'   => $user->createToken('auth_token')->plainTextToken
        ]);
    }

    // =====================================
    // VENDOR HANDLER (FIXED - IMAGES REMOVED FROM VENDOR RESPONSE)
    // =====================================
    protected function handleVendor(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'email'          => 'required|email',
            'name'           => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'repair_service' => 'nullable|boolean',
            'location'       => 'required|string|max:500',
            'latitude'       => 'nullable|numeric',
            'longitude'      => 'nullable|numeric',
            'cnic_front'     => 'required|image|mimes:jpg,jpeg,png|max:4096',
            'cnic_back'      => 'required|image|mimes:jpg,jpeg,png|max:4096',
            'image'          => 'nullable',
            'image.*'        => 'image|mimes:jpg,jpeg,png|max:4096',
            'fcm_token'      => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $socialColumn = $request->login_type === 'apple' ? 'apple_social_id' : 'google_social_id';

        // Check if vendor already exists with same email or social_id
        $vendor = Vendor::where('email', $request->email)
                    ->orWhere($socialColumn, $request->social_id)
                    ->first();

        if ($vendor) {
            // Update existing vendor
            $vendor->update([
                'name'          => $data['name'],
                'phone'         => $data['phone'],
                'location'      => $data['location'],
                'latitude'      => $data['latitude'],
                'longitude'     => $data['longitude'],
                'login_type'    => $data['login_type'],
                'fcm_token'     => $data['fcm_token'] ?? $vendor->fcm_token,
                'repair_service' => $data['repair_service'] ?? 0,
                'status'        => "pending",
                $socialColumn   => $data['social_id']
            ]);
        } else {
            // Create new vendor
            $vendor = Vendor::create([
                'email'         => $data['email'],
                'name'          => $data['name'],
                'phone'         => $data['phone'],
                'location'      => $data['location'],
                'latitude'      => $data['latitude'],
                'longitude'     => $data['longitude'],
                'login_type'    => $data['login_type'],
                'fcm_token'     => $data['fcm_token'] ?? null,
                'repair_service' => $data['repair_service'] ?? 0,
                'status'        => "pending",
                $socialColumn   => $data['social_id']
            ]);
        }

        // CNIC UPLOAD
        $cnicPath = public_path("admin/assets/images/cnic/");
        if (!file_exists($cnicPath)) {
            mkdir($cnicPath, 0755, true);
        }

        if ($request->hasFile('cnic_front')) {
            if ($vendor->cnic_front && file_exists(public_path($vendor->cnic_front))) {
                @unlink(public_path($vendor->cnic_front));
            }
            
            $file = uniqid() . "_front." . $request->file('cnic_front')->extension();
            $request->file('cnic_front')->move($cnicPath, $file);
            $vendor->cnic_front = "admin/assets/images/cnic/$file";
        }

        if ($request->hasFile('cnic_back')) {
            if ($vendor->cnic_back && file_exists(public_path($vendor->cnic_back))) {
                @unlink(public_path($vendor->cnic_back));
            }
            
            $file = uniqid() . "_back." . $request->file('cnic_back')->extension();
            $request->file('cnic_back')->move($cnicPath, $file);
            $vendor->cnic_back = "admin/assets/images/cnic/$file";
        }

        $vendor->save();

        // SHOP IMAGES UPLOAD
        $shopImages = [];
        if ($request->hasFile('image')) {
            $shopPath = public_path("admin/assets/images/shops/");
            if (!file_exists($shopPath)) {
                mkdir($shopPath, 0755, true);
            }

            // Purani images delete karo
            $oldImages = VendorImage::where('vendor_id', $vendor->id)->get();
            foreach ($oldImages as $oldImage) {
                if ($oldImage->image && file_exists(public_path($oldImage->image))) {
                    @unlink(public_path($oldImage->image));
                }
                $oldImage->delete();
            }

            // New images upload karo
            $uploadedFiles = is_array($request->image) ? $request->image : [$request->image];
            foreach ($uploadedFiles as $img) {
                $file = uniqid() . "_shop." . $img->extension();
                $img->move($shopPath, $file);

                VendorImage::create([
                    'vendor_id' => $vendor->id,
                    'image'     => "admin/assets/images/shops/$file"
                ]);

                $shopImages[] = "admin/assets/images/shops/$file";
            }
        }

        // EMAIL SEND
        try {
            Mail::to($vendor->email)->send(new VendorRequestForRegister($vendor));
        } catch (\Exception $e) {
            \Log::error('Vendor registration email failed: ' . $e->getMessage());
        }

        // ✅ FIX: Vendor object se images relationship remove kiya
        // Fresh vendor data WITHOUT images relationship
        $vendorData = $vendor->toArray();
        
        // Agar shop images upload nahi hui, to database se fetch karo
        if (empty($shopImages)) {
            $shopImages = VendorImage::where('vendor_id', $vendor->id)->pluck('image')->toArray();
        }

        return response()->json([
            'status'       => true,
            'message'      => "Vendor registered successfully.",
            'vendor'       => $vendorData, // ✅ Images array nahi hoga
            'shop_images'  => $shopImages, // ✅ Alag se shop_images array
            'token'        => $vendor->createToken('auth_token')->plainTextToken
        ]);
    }
    
}