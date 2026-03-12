<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SideMenue;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    //

   public function index()
{
    $roles = Role::all();
    $permissions = Permission::all(); // Each permission should have: role_id, side_menue_id, permission_type
    $sideMenus = SideMenue::all();
    
    return view('admin.rolepermission.index', compact('roles', 'permissions', 'sideMenus'));
}





}
