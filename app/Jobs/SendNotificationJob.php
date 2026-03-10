<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use App\Models\Vendor;
use App\Models\NotificationTarget;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;
    protected array $userIds;

    public function __construct(array $data, array $userIds)
    {
        $this->data = $data;
        $this->userIds = $userIds;
    }

    public function handle(): void
    {
        Log::info($this->data);
        $notification = Notification::create([
            'user_type' => $this->data['user_type'],
            'title' => $this->data['title'],
            'description' => $this->data['description'],
            'sent_by' => $this->data['sent_by'],
        ]);

        foreach ($this->userIds as $user) {
            if (!isset($user['id'], $user['type'])) {
                continue;
            }

            $modelClass = $user['type'] === 'users' ? User::class : Vendor::class;
            $model = $modelClass::find($user['id']);

            if (!$model) continue;

            NotificationTarget::create([
                'notification_id' => $notification->id,
                'targetable_id' => $model->id,
                'targetable_type' => $modelClass,
            ]);

            if (!empty($model->fcm_token)) {
                NotificationHelper::sendFcmNotification(
                    $model->fcm_token,
                    $this->data['title'],
                    $this->data['description'],
                    [
                        'user_type' => $this->data['user_type'],
                        'notification_id' => $notification->id,
                    ]
                );
            }
        }
    }
}
