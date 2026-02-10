<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Repositories\Api\Interfaces\NotificationRepoInterface;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $notificationRepository;
    

    public function __construct(NotificationRepoInterface $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;

    }

    public function index(Request $request)
    {
        try {
            $user = auth()->guard('vendors')->user() ?? auth()->user();
            if (!$user) {
                return ResponseHelper::error(null, 'Unauthorized', 'error', 401);
            }
            $notifications = $this->notificationRepository->getUserNotifications($user);
            return ResponseHelper::success($notifications, 'Notifications fetched successfully', 'success', 200);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 'Failed to fetch notifications', 'error', 500);
        }
    }


public function seenNotification(Request $request, $notificationId)
{
    try {
        $user = auth()->guard('vendors')->user() ?? auth()->user();

        if (!$user) {
            return ResponseHelper::error(null, 'Unauthorized', 'error', 401);
        }

        // now notification id comes from URL param
        $result = $this->notificationRepository->markAsSeen($user, $notificationId);

        if (!$result['status']) {
            return ResponseHelper::error(null, $result['message'], 'error', 404);
        }

        return ResponseHelper::success(['seen' => $result['seen']], 'Notification marked as seen', 'success', 200);

    } catch (\Exception $e) {
        return ResponseHelper::error($e->getMessage(), 'Failed to mark notification as seen', 'error', 500);
    }
}

// public function deleteNotification($notificationId)
// {
//     try {
//         $user = auth()->guard('vendors')->user() ?? auth()->user();

//         if (!$user) {
//             return ResponseHelper::error(null, 'Unauthorized', 'error', 401);
//         }

//         $notification = Notification::find($notificationId);

//         if (!$notification) {
//             return ResponseHelper::error(null, 'Notification not found', 'error', 404);
//         }

//         // Ensure the notification belongs to the user
//         $target = NotificationTarget::where('notification_id', $notificationId)
//             ->where('targetable_id', $user->id)
//             ->where('targetable_type', get_class($user))
//             ->first();

//         if (!$target) {
//             return ResponseHelper::error(null, 'Notification does not belong to the user', 'error', 403);
//         }

//         // Delete the notification and its target
//         $target->delete();
//         $notification->delete();

//         return ResponseHelper::success(null, 'Notification deleted successfully', 'success', 200);

//     } catch (\Exception $e) {
//         return ResponseHelper::error($e->getMessage(), 'Failed to delete notification', 'error', 500);
//     }



// }

public function deleteAllNotifications()
{
    try {
        $user = auth()->guard('vendors')->user() ?? auth()->user();

        if (!$user) {
            return ResponseHelper::error(null, 'Unauthorized', 'error', 401);
        }

        NotificationTarget::where('targetable_id', $user->id)
            ->where('targetable_type', get_class($user))
            ->delete();

        return ResponseHelper::success(null, 'All notifications cleared successfully', 'success', 200);

    } catch (\Exception $e) {
        return ResponseHelper::error($e->getMessage(), 'Failed to clear notifications', 'error', 500);
    }
}




}
