<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Services\VendorService;
use App\Models\UserRolePermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVendorRequest;
use App\Http\Requests\VendorRequest;
use App\Models\VendorImage;
use Illuminate\Support\Facades\Auth;


class VendorController extends Controller
{
    protected $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
    }

    public function index()
    {
        $users = $this->vendorService->getAllUsers();
        
        $sideMenuPermissions = collect();

        if (!Auth::guard('admin')->check()) {
            $user = Auth::guard('subadmin')->user()->load('roles');
            $permissions = UserRolePermission::with(['permission', 'sideMenue'])
                ->where('role_id', $user->role_id)
                ->get();
            $sideMenuPermissions = $permissions->groupBy('sideMenue.name')->map(function ($items) {
                return $items->pluck('permission.name');
            });
        }

        return view('admin.vendor.index', compact('users', 'sideMenuPermissions'));
    }

     public function vendorpendingCounter()
    {
        $count = $this->vendorService->pendingVendorCount();
        return response()->json(['count' => $count]);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:vendors,id',
            'status' => 'required|in:pending,activated,deactivated',
            'reason' => 'nullable|string|max:255',
        ]);

        $vendor = $this->vendorService->updateStatus(
            $request->id,
            $request->status,
            $request->reason
        );

        if (!$vendor) {
            return response()->json(['success' => false, 'message' => 'Vendor not found']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
            'new_status' => ucfirst($vendor->status),
        ]);
    }


    public function createView()
    {
        return view('admin.vendor.create');
    }

    public function create(VendorRequest $request)
    {
        $this->vendorService->createUser($request);
        return redirect()->route('vendor.index')->with('success', 'Vendor Created Successfully');
    }

    public function edit($id)
    {
        $user = $this->vendorService->findUser($id);
        return view('admin.vendor.edit', compact('user'));
    }

    public function update(UpdateVendorRequest $request, $id)
    {
        $data = $request->only([
            'name',
            'email',
            'phone',
            'location',
            'repair_service'
        ]);

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }

        if ($request->hasFile('cnic_front')) {
            $data['cnic_front'] = $request->file('cnic_front');
        }

        if ($request->hasFile('cnic_back')) {
            $data['cnic_back'] = $request->file('cnic_back');
        }

        if ($request->hasFile('shop_images')) {
            $data['shop_images'] = $request->file('shop_images');
        }

        $this->vendorService->updateUser($id, $data);

        return redirect('/admin/vendor')->with('success', 'Vendor Updated Successfully');
    }



    public function delete($id)
    {
        $deleted = $this->vendorService->deleteUser($id);
        return redirect()->back()->with($deleted ? 'success' : 'error', $deleted ? 'Vendor Deleted Successfully' : 'User not found');
    }
}
