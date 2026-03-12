<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SideMenue;
use App\Models\UserRole;
use App\Models\SideMenuHasPermission;
use App\Models\UserRolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    //

    public function index()
    {
        $roles = Role::all();
        $sideMenuPermissions = collect();
        if (!Auth::guard('admin')->check()) {
            $user = Auth::guard('subadmin')->user()->load('roles');
            $roleId = $user->role_id;
            $permissions = UserRolePermission::with(['permission', 'sideMenue'])
                ->where('role_id', $roleId)
                ->get();
            $sideMenuPermissions = $permissions->groupBy('sideMenue.name')->map(function ($items) {
                return $items->pluck('permission.name'); // ['view', 'create']
            });
        }
        $rolesUsed = UserRole::pluck('role_id')->toArray();
        return view('admin.rolepermission.index', compact('roles', 'sideMenuPermissions', 'rolesUsed'));
    }

    public function create()
    {
        return view('admin.rolepermission.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
        ]);
        $role = Role::create([
            'name' => $request->name,
        ]);
        return redirect('admin/roles')->with('message', 'Role created successfully');
    }


    public function delete($id)
    {
        $find = Role::find($id);
        if ($find) {
            $find->delete();
            return redirect()->back()->with('success', 'Role deleted successfully');
        } else {
            return redirect()->back()->with('error', 'Role not found');
        }
    }


    public function permissions($id)
    {
        $roles = Role::find($id);
        $permissions = Permission::all();
        $sideMenus = SideMenue::all();

        // Jo permissions pehle se assign hain is role ko
        $existingPermissions = UserRolePermission::where('role_id', $id)
            ->with(['permission', 'sideMenue'])
            ->get()
            ->toArray();

        // Har sidemenu ke sath uske allowed permissions (jaise view/edit)
        $sideMenuPermissions = SideMenuHasPermission::with('permission')
            ->get()
            ->groupBy('side_menu_id');

        // dd($sideMenuPermissions);

        return view('admin.rolepermission.permissions', compact(
            'roles',
            'permissions',
            'sideMenus',
            'sideMenuPermissions',
            'existingPermissions'
        ));
    }


    public function storePermissions(Request $request, $roleId)
    {
        // return $request->all();
        // Step 1: Clear all old permissions for this role
        UserRolePermission::where('role_id', $roleId)->delete();

        // Step 2: Loop through all selected permissions and save
        if ($request->has('permissions')) {
            foreach ($request->permissions as $sideMenuId => $permissionNames) {
                foreach ($permissionNames as $permissionName) {
                    $permission = Permission::where('name', $permissionName)->first();
                    // return $permission->id;

                    if ($permission) {
                        UserRolePermission::create([
                            'role_id'       => $request->role_id,
                            'side_menu_id' => $sideMenuId,   // 👈 spelling mistake hogi
                            'permission_id' => $permission->id,
                        ]);



                        // dd($permission->id);
                    }
                }
            }
        }

        return redirect('/admin/roles')->with('success', 'Permissions saved successfully');
    }



    function hasPermission($menu, $permission)
    {
        if (Auth::guard('admin')->check()) {
            return true;
        }

        $user = Auth::user();
        $roleId = $user->role_id;

        $permissions = \App\Models\UserRolePermission::with(['permission', 'sideMenue'])
            ->where('role_id', $roleId)
            ->get()
            ->groupBy('sideMenue.name')
            ->map(fn($items) => $items->pluck('permission.name'));

        return $permissions->has($menu) && $permissions[$menu]->contains($permission);
    }
}
