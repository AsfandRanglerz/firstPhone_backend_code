<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\User;
use App\Models\UserRolePermission;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $users = $this->userService->getAllUsers();
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

        return view('users.index', compact('users', 'sideMenuPermissions'));
    }

    public function toggleStatus(Request $request)
    {
        $user = $this->userService->toggleUserStatus($request->id, $request->status, $request->reason);
        if ($user) {
            return response()->json([
                'success' => true,
                'message' => 'Status Updated Successfully',
                'new_status' => $user->toggle ? 'Activated' : 'Deactivated'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }

    public function createView()
    {
        return view('users.create');
    }

    public function create(CustomerRequest $request)
    {

        $this->userService->createUser($request);
        return redirect()->route('user.index')->with('success', 'Customer created successfully');
    }

    public function edit($id)
    {
        $user = $this->userService->findUser($id);
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required',
            'email' => [
                'required',
                'email',
                'regex:/^[\w\.\-]+@[a-zA-Z0-9\-]+\.[a-zA-Z]{2,6}$/',
                'unique:users,email' . $id
            ],
            'phone' => 'required|regex:/^[0-9]+$/|max:15',
        ]);
        $data = $request->only(['name', 'email', 'phone']);
        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image');
        }
        $this->userService->updateUser($id, $data);
        return redirect('/admin/user')->with('success', 'Customer updated successfully');
    }

    public function delete($id)
    {
        $deleted = $this->userService->deleteUser($id);
        return redirect()->back()->with($deleted ? 'success' : 'error', $deleted ? 'Customer deleted successfully' : 'User not found');
    }

    public function deleteSelected(Request $request)
{
    User::whereIn('id',$request->ids)->delete();

    return response()->json(['success'=>true]);
}

public function deleteAll()
{
    User::truncate();

    return response()->json(['success'=>true]);
}
}
