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
        // $users = $this->userService->getAllUsers();
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

        return view('users.index', compact('sideMenuPermissions'));
    }

    public function getUsersData(Request $request)
{

    $sideMenuPermissions = collect();
    

if (!Auth::guard('admin')->check()) {
    $user = Auth::guard('subadmin')->user();

    $roleId = $user->roles->first()->id ?? null;

    $permissions = UserRolePermission::with(['permission', 'sideMenue'])
        ->where('role_id', $roleId)
        ->get();

    $sideMenuPermissions = $permissions->groupBy('sideMenue.name')
        ->map(fn($items) => $items->pluck('permission.name'));
}
    $query = User::select('id','name','email','phone','image','toggle','created_at')
        ->orderBy('id','desc');

    return datatables()->of($query)
        ->addIndexColumn()

        ->editColumn('created_at', function ($user) {
            return $user->created_at
                ? $user->created_at->timezone('Asia/Karachi')->format('d M Y, h:i A')
                : '';
        })

        ->editColumn('email', function ($user) {
            return '<a href="mailto:'.$user->email.'">'.$user->email.'</a>';
        })

        ->editColumn('phone', function ($user) {
            return '<a href="tel:'.$user->phone.'">'.$user->phone.'</a>';
        })

        ->editColumn('image', function ($user) {
            return $user->image
                ? '<img src="'.asset($user->image).'" width="50" height="50">'
                : '<img src="'.asset('public/admin/assets/images/default.png').'" width="50" height="50" alt="Default Image">';
        })

        ->addColumn('toggle', function ($user) use ($sideMenuPermissions) {
            if (
            Auth::guard('admin')->check() ||
            ($sideMenuPermissions->has('Customers') &&
            $sideMenuPermissions['Customers']->contains('status'))
            )
           { $checked = $user->toggle ? 'checked' : '';
            $statusText = $user->toggle ? 'Activated' : 'Deactivated';

            return '
            <label class="custom-switch">
                <input type="checkbox" class="custom-switch-input toggle-status"
                    data-id="'.$user->id.'" '.$checked.'>
                <span class="custom-switch-indicator"></span>
                <span class="custom-switch-description">'.$statusText.'</span>
            </label>';
           }
        })

        ->addColumn('actions', function ($user) use ($sideMenuPermissions) {

    $buttons = '<div class="d-flex gap-1">';

    // ✅ EDIT BUTTON
    if (
        Auth::guard('admin')->check() ||
        ($sideMenuPermissions->has('Customers') &&
        $sideMenuPermissions['Customers']->contains('edit'))
    ) {
        $buttons .= '
        <a href="'.route('user.edit',$user->id).'" class="btn btn-primary">
            <i class="fa fa-edit"></i>
        </a>';
    }

    // ✅ DELETE BUTTON
    // if (
    //     Auth::guard('admin')->check() ||
    //     ($sideMenuPermissions->has('Customers') &&
    //     $sideMenuPermissions['Customers']->contains('delete'))
    // ) {
    //     $buttons .= '
    //     <form id="delete-form-'.$user->id.'" 
    //         action="'.route('user.delete',$user->id).'" 
    //         method="POST" style="display:inline;">
    //         '.csrf_field().'
    //         '.method_field('DELETE').'
    //     </form>

    //     <button class="show_confirm btn" 
    //         style="background-color: #009245;"
    //         data-form="delete-form-'.$user->id.'" 
    //         type="button">
    //         <i class="fa fa-trash"></i>
    //     </button>';
    // }

    $buttons .= '</div>';

    return $buttons;
})

        ->rawColumns(['email','phone','image','toggle','actions'])
        ->make(true);
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
        return redirect()->route('user.index')->with('success', 'Customer Created Successfully');
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
                'unique:users,email,' . $id
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
        return redirect('/admin/user')->with('success', 'Customer Updated Successfully');
    }

    public function delete($id)
    {
        $deleted = $this->userService->deleteUser($id);
        return redirect()->back()->with($deleted ? 'success' : 'error', $deleted ? 'Customer Deleted Successfully' : 'User Not Fund');
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
