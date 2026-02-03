<?php

namespace App\Repositories\Api;

use App\Models\User;
use App\Models\Vendor;
use App\Models\EmailOtp;
use App\Mail\UserEmailOtp;
use App\Mail\ForgotOTPMail;
use App\Models\VendorImage;
use App\Mail\VerifyEmailOtp;
use Illuminate\Http\Request;
use App\Mail\UserCredentials;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Repositories\Api\Interfaces\AuthRepositoryInterface;
use App\Mail\CustomerRegister;
use App\Mail\VendorRequestForRegister;

class AuthRepository implements AuthRepositoryInterface
{

    public function login(array $request)
    {
        if ($request['type'] === 'customer') {
            $user = User::where('email', $request['email'])->first();

            if ($user && isset($user->toggle) && $user->toggle == 0) {
                return ['error' => 'Your account has been deactivated.'];
            }
        } elseif ($request['type'] === 'vendor') {
            $user = Vendor::where('email', $request['email'])->first();

            if ($user) {
                if ($user->status === 'pending') {
                    return ['error' => 'Your account is under review. Please wait for admin approval.'];
                }
                if ($user->status === 'deactivated') {
                    return ['error' => 'Your account has been deactivated.'];
                }
            }
        } else {
            return ['error' => 'Invalid user type'];
        }

        if (!$user) {
            return ['error' => ucfirst($request['type']) . ' not found'];
        }

        if (!Hash::check($request['password'], $user->password)) {
            return ['error' => 'Invalid credentials'];
        }

        $token = $user->createToken($request['type'] . '_token')->plainTextToken;
        $user->token = $token;

        return [
            'user' => $user,
        ];
    }

    public function register(array $request)
    {
        // Generate OTP
        $otp = rand(1000, 9999);

        // Store OTP & pending user in session (no user created yet)
        session([
            'otp' => $otp,
            'pending_user' => $request
        ]);

        // Send OTP to email
        Mail::to($request['email'])->send(new VerifyEmailOtp($otp));

        return [
            'status' => 200,
            'message' => 'OTP sent to your email. Please verify to complete registration.',
            'data' => [
                'email' => $request['email']
            ]
        ];
    }

    /**
     * Send OTP
     */
    public function sendOtp(array $request)
    {
        // Check if email already exists
        if ($request['type'] === 'customer') {
            $existing = User::where('email', $request['email'])->first();
        } elseif ($request['type'] === 'vendor') {
            $existing = Vendor::where('email', $request['email'])->first();
        } else {
            return ['error' => 'Invalid user type'];
        }

        if ($existing) {
            return ['error' => 'Email already registered. Please login instead.'];
        }

        // Generate OTP
        $otp = rand(1000, 9999);

        // Store OTP in cache for 10 minutes
        Cache::put('otp_' . $request['email'], $otp, now()->addMinutes(10));

        // Send email
        Mail::to($request['email'])->send(new VerifyEmailOtp($otp));

        return [
            'status' => 200,
            'message' => 'OTP sent successfully to your email.',
            'data' => ['email' => $request['email']]
        ];
    }

    /**
     * Verify OTP and Create User/Vendor
     */
    public function verifyOtp(Request $request)
    {
        $cacheKey = 'otp_' . $request['email'];
        $cachedOtp = Cache::get($cacheKey);

        if (!$cachedOtp) {
            return [
                'status' => 'error',
                'message' => 'OTP expired or invalid. Please request again.'
            ];
        }

        if ($cachedOtp != $request['otp']) {
            return [
                'status' => 'error',
                'message' => 'Invalid OTP'
            ];
        }

        $plainPassword = $request['password'];

        /*
        * --------------------------------------------------
        *  CUSTOMER REGISTRATION
        * --------------------------------------------------
        */
        if ($request['type'] === 'customer') 
        { 
            // Handle profile image
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = uniqid().'_profile.'.$file->getClientOriginalExtension();
                $file->move(public_path('admin/assets/images/users/'), $filename);
                $imagePath = 'public/admin/assets/images/users/' . $filename;
            } else {
                $imagePath = 'public/admin/assets/images/default.png';
            }

            // Create user
            $user = User::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'phone' => $request['phone'] ?? null,
                'password' => Hash::make($plainPassword),
                'toggle' => 1,
                'image' => $imagePath,
            ]);

            $user->plain_password = $plainPassword;

            // Mail to customer
            Mail::to($user->email)->send(new CustomerRegister($user));

            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'image' => $user->image,
            ];
        }


        /*
        * --------------------------------------------------
        *  VENDOR REGISTRATION
        * --------------------------------------------------
        */
        else 
        {
            // Create folder if not exist
            $cnicPath = public_path('admin/assets/images/cnic/');
            if (!file_exists($cnicPath)) {
                mkdir($cnicPath, 0777, true);
            }

            // Save CNIC Front
            $cnicFrontPath = null;
            if ($request->hasFile('cnic_front')) {
                $frontName = uniqid().'_cnic_front.'.$request->file('cnic_front')->getClientOriginalExtension();
                $request->file('cnic_front')->move($cnicPath, $frontName);
                $cnicFrontPath = 'public/admin/assets/images/cnic/' . $frontName;
            }

            // Save CNIC Back
            $cnicBackPath = null;
            if ($request->hasFile('cnic_back')) {
                $backName = uniqid().'_cnic_back.'.$request->file('cnic_back')->getClientOriginalExtension();
                $request->file('cnic_back')->move($cnicPath, $backName);
                $cnicBackPath = 'public/admin/assets/images/cnic/' . $backName;
            }

            // Create Vendor
            $vendor = Vendor::create([
                'name'        => $request['name'],
                'email'       => $request['email'],
                'phone'       => $request['phone'] ?? null,
                'location'    => $request['location'] ?? null,
                'latitude'    => $request['latitude'],
                'longitude'   => $request['longitude'],
                'password'    => Hash::make($plainPassword),
                'cnic_front'  => $cnicFrontPath,
                'cnic_back'   => $cnicBackPath,
                'repair_service' => $request['repair_service'] ?? 0,
                'status'      => 'pending',
            ]);

            $vendor->plain_password = $plainPassword;


            // Save Shop Images
            if ($request->hasFile('image')) {

                $shopPath = public_path('admin/assets/images/shops/');
                if (!file_exists($shopPath)) {
                    mkdir($shopPath, 0777, true);
                }

                foreach ($request->file('image') as $index => $image) {
                    if ($image->isValid()) {
                        $fileName = uniqid().'_shop_'.$index.'.'.$image->getClientOriginalExtension();
                        $image->move($shopPath, $fileName);

                        $vendor->images()->create([
                            'image' => 'public/admin/assets/images/shops/' . $fileName
                        ]);
                    }
                }
            }

            $shopImages = $vendor->images()->pluck('image')->toArray();

            $data = [
                'id'         => $vendor->id,
                'name'       => $vendor->name,
                'email'      => $vendor->email,
                'phone'      => $vendor->phone,
                'location'   => $vendor->location,
                'latitude'   => $vendor->latitude,
                'longitude'  => $vendor->longitude,
                'cnic_front' => $vendor->cnic_front,
                'cnic_back'  => $vendor->cnic_back,
                'images'     => $shopImages,
            ];

            // Send vendor email
            Mail::to($vendor->email)->send(new VendorRequestForRegister($vendor));
        }


        // Clear OTP
        Cache::forget($cacheKey);

        return [
            'status' => 'success',
            'message' => 'OTP verified and registration completed successfully.',
            'data' => $data
        ];
    }


    public function resendOtp(array $request)
    {
        $cacheKey = 'otp_' . $request['email'];

        // Check if user already requested OTP before
        // if (!Cache::has($cacheKey)) {
        //     return [
        //         'status' => 'error',
        //         'message' => 'No OTP session found or expired. Please register again.'
        //     ];
        // }

        // Generate new OTP
        $newOtp = rand(1000, 9999);

        // Store new OTP for another 10 minutes
        Cache::put($cacheKey, $newOtp, now()->addMinutes(10));

        // Send new OTP
        Mail::to($request['email'])->send(new \App\Mail\VerifyEmailOtp($newOtp));

        return [
            'status' => 'success',
            'message' => 'New OTP sent successfully.',
            'data' => ['email' => $request['email']]
        ];
    }

    public function forgotPasswordResendOtp($data)
    {
        $user = null;

        if ($data['type'] === 'customer') {
            $user = \App\Models\User::where('email', $data['email'])->first();
        } elseif ($data['type'] === 'vendor') {
            $user = \App\Models\Vendor::where('email', $data['email'])->first();
        }

        if (!$user) {
            return [
                'status' => 'error',
                'message' => ucfirst($data['type']) . ' not found with this email.',
            ];
        }

        // Generate new OTP
        $otp = rand(1000, 9999);
        $user->otp = $otp;
        $user->save();

        // Send OTP mail
        Mail::to($user->email)->send(new ForgotOTPMail($otp));

        return [
            'status' => 'success',
            'message' => 'OTP resent successfully.',
            'data' => [
                'email' => $user->email,
            ],
        ];
    }


    public function forgotPasswordSendOtp(array $request)
    {
        if ($request['type'] === 'customer') {
            $user = \App\Models\User::where('email', $request['email'])->first();
        } elseif ($request['type'] === 'vendor') {
            $user = \App\Models\Vendor::where('email', $request['email'])->first();
        } else {
            return ['status' => 'error', 'message' => 'Invalid user type'];
        }

        if (!$user) {
            return ['status' => 'error', 'message' => 'Email not found'];
        }


        $otp = rand(1000, 9999);
        Cache::put('forgot_otp_' . $request['email'], $otp, now()->addMinutes(10));

        Mail::to($request['email'])->send(new ForgotOTPMail($otp));

        return ['status' => 'success', 'message' => 'OTP sent successfully to your email.', 'data' => ['email' => $request['email']]];
    }

    public function forgotPasswordVerifyOtp(array $request)
    {
        $cacheKey = 'forgot_otp_' . $request['email'];
        $cachedOtp = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (!$cachedOtp) {
            return ['status' => 'error', 'message' => 'OTP expired or invalid.'];
        }

        if ($cachedOtp != $request['otp']) {
            return ['status' => 'error', 'message' => 'Invalid OTP'];
        }

        // Clear OTP after verification
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        // Mark email verified for password reset
        \Illuminate\Support\Facades\Cache::put('forgot_verified_' . $request['email'], true, now()->addMinutes(10));

        return ['status' => 'success', 'message' => 'OTP verified successfully.'];
    }

    public function forgotPasswordReset(\Illuminate\Http\Request $request)
    {
        $verified = \Illuminate\Support\Facades\Cache::get('forgot_verified_' . $request->email);
        if (!$verified) {
            return [
                'status' => 'error',
                'message' => 'OTP verification required before resetting password.'
            ];
        }

        $user = $request->type === 'customer'
            ? \App\Models\User::where('email', $request->email)->first()
            : \App\Models\Vendor::where('email', $request->email)->first();

        if (!$user) {
            return [
                'status' => 'error',
                'message' => 'User not found.'
            ];
        }

        // ❌ Do NOT return JsonResponse here
        if (\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return [
                'status' => 'error',
                'message' => 'This is your current password. Please enter a different one.'
            ];
        }

        // ✅ Reset password
        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();

        \Illuminate\Support\Facades\Cache::forget('forgot_verified_' . $request->email);

        return [
            'status' => 'success',
            'message' => 'Password reset successfully.'
        ];
    }


    public function logout()
    {
        $user = auth()->user();
        if ($user) {
            $user->tokens()->delete();
            return true;
        }
        return ['error' => 'User not authenticated'];
    }
    public function updateProfile(array $request)
    {
        if ($request['type'] === 'customer') {
            $user = User::where('email', $request['email'])->first();
        } elseif ($request['type'] === 'vendor') {
            $user = Vendor::where('email', $request['email'])->first();
        } else {
            return ['error' => 'Invalid user type'];
        }
        if (!$user) {
            return ['error' => ucfirst($request['type']) . ' not found'];
        }
        $user->name = $request['name'] ?? $user->name;
        $user->phone = $request['phone'] ?? $user->phone;
        $user->email = $request['email'] ?? $user->email;
        if (isset($request['image']) && $request['image'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $request['image'];
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '.' . $extension;
            $file->move(public_path('admin/assets/images/users'), $filename);
            $user->image = 'public/admin/assets/images/users/' . $filename;
        }

        if ($request['type'] === 'vendor') {
        if (isset($request['repair_service'])) {
            $user->repair_service = $request['repair_service'];
        }
        if (isset($request['location'])) {
            $user->location = $request['location'];
        }
     }

        $user->save();
    }
    public function changePassword(array $request)
    {
        $user = auth()->user();
        if (!$user) {
            return ['error' => 'User not authenticated'];
        }
        if ($request['new_password'] !== $request['confirm_password']) {
            return ['error' => 'New password and confirm password do not match'];
        }
        if (Hash::check($request['new_password'], $user->password)) {
            return ['error' => 'New password cannot be the same as the old password'];
        }
        $user->password = Hash::make($request['new_password']);
        $user->save();
        return $user;
    }

   public function checkEmail($email)
{
    $customer = User::where('email', $email)->first();
    $vendor   = Vendor::where('email', $email)->with('images')->first();

    $vendorStatusMessage = null;

    if ($vendor) {
        if ($vendor->status == 'pending') {
            $vendorStatusMessage = 'Your account is under review. Please wait for admin approval.';
        } elseif ($vendor->status == 'deactivated') {
            $vendorStatusMessage = 'Your account has been deactivated.';
        } 
    }


    if (!$customer && !$vendor) {
        return [
            'status' => 'not_found',
            'message' => 'No account found with this email',
        ];
    }

    // Get latest tokens
    $customerToken = $customer
        ? optional($customer->tokens()->latest()->first())->token
        : null;

    $vendorToken = $vendor
        ? optional($vendor->tokens()->latest()->first())->token
        : null;

    // ⭐ CASE 1: CUSTOMER ONLY
    if ($customer && !$vendor) {
        return [
            'status' => 'success',
            'message' => 'This email belongs to a customer',
            'customer' => [
                'data' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'image' => $customer->image,
                ],
                'token' => $customerToken,
            ],
        ];
    }

    // ⭐ CASE 2: VENDOR ONLY
    if ($vendor && !$customer) {
        return [
            'status' => 'success',
            'message' => 'This email belongs to a vendor',
            'vendor' => [
                'data' => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'email' => $vendor->email,
                    'phone' => $vendor->phone,
                    'location' => $vendor->location,
                    'cnic_front' => $vendor->cnic_front,
                    'cnic_back' => $vendor->cnic_back,
                    'images' => $vendor->images->pluck('image')->toArray(),
                    'status' => $vendor->status,
                    'status_message' => $vendorStatusMessage,
                ],
                'token' => $vendorToken,
            ],
        ];
    }

    // ⭐ CASE 3: BOTH CUSTOMER & VENDOR
    return [
        'status' => 'success',
        'message' => 'Email exists as both customer and vendor',

        'customer' => [
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'image' => $customer->image,
            ],
            'token' => $customerToken,
        ],

        'vendor' => [
            'data' => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'location' => $vendor->location,
                'cnic_front' => $vendor->cnic_front,
                'cnic_back' => $vendor->cnic_back,
                'images' => $vendor->images->pluck('image')->toArray(),
                'status' => $vendor->status,
                'status_message' => $vendorStatusMessage,
            ],
            'token' => $vendorToken,
        ],
    ];
}

}
