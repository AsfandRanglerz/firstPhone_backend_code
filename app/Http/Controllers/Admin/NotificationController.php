<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\SubAdmin;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Jobs\NotificationJob;
use App\Models\AdminNotification;
use App\Models\UserRolePermission;
use App\Models\Vendor;
use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use App\Models\NotificationTarget;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Jobs\SendNotificationJob;

class NotificationController extends Controller
{

    protected NotificationRepositoryInterface $notificationRepository;

    public function __construct(NotificationRepositoryInterface $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    public function index()
    {
        $notifications = Notification::with('targets.targetable')
        ->where('sent_by', 'admin')
        ->where('delete_by_admin', 0)
        ->latest()
        ->get();

        $users = User::all();
        $subadmin = SubAdmin::all();
        $vendors = Vendor::all();

        $sideMenuPermissions = collect();

        if (!Auth::guard('admin')->check()) {

            $user = Auth::guard('subadmin')->user()->load('roles');

            // 1. Get role_id of subadmin
            $roleId = $user->role_id;

            // 2. Get all permissions assigned to this role
            $permissions = UserRolePermission::with(['permission', 'sideMenue'])
                ->where('role_id', $roleId)
                ->get();

            // 3. Group permissions by side menu
            $sideMenuPermissions = $permissions->groupBy('sideMenue.name')->map(function ($items) {
                return $items->pluck('permission.name');
            });
        }

        return view('admin.notification.index', compact('notifications', 'sideMenuPermissions', 'users', 'subadmin', 'vendors'));
    }


    public function store(NotificationRequest $request)
    {
        $users = [];

        if ($request->user_type === 'customers') {
            $request->validate([
                'users.*' => 'exists:users,id',
            ]);
            $users = array_map(fn($id) => ['id' => $id, 'type' => 'users'], $request->users);
        } elseif ($request->user_type === 'vendors') {
            $request->validate([
                'users.*' => 'exists:vendors,id',
            ]);
            $users = array_map(fn($id) => ['id' => $id, 'type' => 'vendors'], $request->users);
        } elseif ($request->user_type === 'all') {
            $userIds = User::whereIn('id', $request->users)->pluck('id')->toArray();
            $vendorIds = Vendor::whereIn('id', $request->users)->pluck('id')->toArray();

            if (empty($userIds) && empty($vendorIds)) {
                return back()->withErrors(['users' => 'No Valid Customer Or Vendor IDs Provided']);
            }

            $users = array_merge(
                array_map(fn($id) => ['id' => $id, 'type' => 'users'], $userIds),
                array_map(fn($id) => ['id' => $id, 'type' => 'vendors'], $vendorIds),
            );
        }


        // Dispatch job
        SendNotificationJob::dispatch([
            'sent_by' => 'admin',
            'user_type' => $request->user_type,
            'title' => $request->title,
            'description' => $request->description,
        ], $users);

        return redirect()->route('notification.index')->with('success', 'Notification Sent Successfully');
    }


    public function destroy(Request $request, $id)
    {
        $notification = Notification::find($id);
        $notification->delete_by_admin = 1;
        $notification->save();
        return redirect()->route('notification.index')->with(['success' => 'Notification Deleted Successfully']);
    }



    public function deleteAll()
    {
        Notification::where('sent_by', 'admin')->where('delete_by_admin', 0)->update(['delete_by_admin' => 1]);
        return redirect()->route('notification.index')->with('success', 'All Notifications Have Been Deleted');
    }

    public function getUsersByType(Request $request)

    {
        $type = $request->type;
        $users = [];
        switch ($type) {
            case 'subadmin':
                $users = SubAdmin::select('id', 'name', 'email')->get();
                break;
            case 'web':
                $users = User::select('id', 'name', 'email')->get();
                break;
        }

        return response()->json($users);
    }
}
