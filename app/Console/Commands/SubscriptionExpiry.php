<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Vendor;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Helpers\NotificationHelper;
use Carbon\Carbon;

class SubscriptionExpiry extends Command
{
    protected $signature = 'subscription:expirynotification';

    protected $description = 'Handle subscription expiry';

    public function handle()
    {
        $vendors = Vendor::whereHas('subscription', function ($query) {
                $query->where('is_active', true)
                      ->whereDate('end_date', Carbon::today());
            })
            ->with('subscription')
            ->get();

        foreach ($vendors as $vendor) {

            // deactivate subscription
            $vendor->subscription->is_active = false;
            $vendor->subscription->save();
            $description = $vendor->subscription->plan_name == 'Free' 
                ? "Your 30-day free trial has ended." 
                : "Your 30-day {$vendor->subscription->plan_name} package has expired.";
            // create notification
            $notification = Notification::create([
                'user_type' => 'vendors',
                'title' => "Subscription Expired",
                'description' => $description,
            ]);

            // notification target
            NotificationTarget::create([
                'notification_id' => $notification->id,
                'targetable_id' => $vendor->id,
                'targetable_type' => Vendor::class,
                'type' => 'subscription_expired',
            ]);

            // send FCM
            NotificationHelper::sendFcmNotification(
                $vendor->fcm_token,
                "Subscription Expired",
                $description,
                [
                    'type' => 'subscription_expired',
                ]
            );
        }

        $this->info('Subscription expiry notifications sent successfully.');

        return Command::SUCCESS;
    }
}